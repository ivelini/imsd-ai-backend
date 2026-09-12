<?php

namespace App\Actions\Booking;

use App\DTOs\Booking\ConfirmBookingInput;
use App\Enums\Booking\BookingSource;
use App\Enums\Booking\BookingStatus;
use App\Models\Booking\Booking;
use App\Models\Booking\BookingItem;
use App\Models\Booking\BookingService;
use App\Models\Booking\Slot;
use App\Models\User;
use App\Services\Booking\PriceCalculator;
use DomainException;
use Illuminate\Database\Connection;
use Illuminate\Support\Str;

/**
 * Создание записи при подтверждении кода (ФТ-8) — единый Action для сайта
 * (и, в будущем, каналов админки). Атомарность: блокировка строки слота
 * `SELECT … FOR UPDATE` внутри транзакции исключает гонку «закрытие vs запись».
 * Код к этому моменту верифицирован (Valid); одноразовость кода — атомарная пометка
 * used_at в той же транзакции. При closeSlot слот помечается закрытым с привязкой к записи.
 */
final readonly class ConfirmBooking
{
    public function __construct(
        private Connection $connection,
        private PriceCalculator $priceCalculator,
    ) {}

    public function execute(ConfirmBookingInput $input): Booking
    {
        return $this->connection->transaction(function () use ($input): Booking {
            $slot = Slot::query()
                ->whereDate('date', $input->date->toDateString())
                ->where('hour', $input->hour)
                ->lockForUpdate()
                ->first();

            // Проверка внутри транзакции с блокировкой строки: предпроверка в Precondition
            // от гонки не спасает (TOCTOU) — здесь финальная защита.
            if ($slot === null || $slot->is_closed) {
                throw new DomainException('Слот недоступен для записи', 409);
            }

            $user = User::query()->firstOrCreate(
                ['phone' => $input->phone],
                ['name' => $input->name],
            );

            $booking = Booking::create([
                'user_id' => $user->id,
                'slot_id' => $slot->id,
                'booking_code_id' => $input->code->id,
                'start_time' => sprintf('%02d:00:00', $input->hour),
                'status' => BookingStatus::Confirmed,
                'source' => BookingSource::Site,
                'idempotency_key' => Str::uuid(),
                'radius' => $input->radius,
                'car_type' => $input->carType,
                'plate' => $input->plate,
                'total_price' => 0, // пересчитается ниже по составу
            ]);

            $services = BookingService::query()
                ->whereIn('id', array_keys($input->quantities))
                ->where('is_active', true)
                ->get();

            // Серверный пересчёт из актуального выбора (ФТ-8): сумма клиентом не передаётся
            $quote = $this->priceCalculator->calculate($services, $input->radius, $input->carType, $input->quantities);

            foreach ($quote->lines as $line) {
                BookingItem::create([
                    'booking_id' => $booking->id,
                    'service_id' => $line->service->id,
                    'price' => $line->unitPrice, // снимок: цена за единицу
                    'quantity' => $line->quantity,
                ]);
            }

            $booking->update(['total_price' => $quote->total]);

            // Бронь с сайта занимает час (ФТ-8/ФТ-16): слот закрывается и привязывается
            // к записи — дополнительные записи в слот — только оператором из админки
            if ($input->closeSlot) {
                $slot->update(['is_closed' => true, 'booking_id' => $booking->id]);
            }

            // Одноразовость кода — атомарно внутри транзакции с созданием записи
            $input->code->update(['used_at' => now()]);

            return $booking;
        });
    }
}
