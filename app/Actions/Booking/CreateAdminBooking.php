<?php

namespace App\Actions\Booking;

use App\DTOs\Booking\CreateAdminBookingInput;
use App\Enums\Booking\BookingSource;
use App\Enums\Booking\BookingStatus;
use App\Models\Booking\Booking;
use App\Models\Booking\BookingItem;
use App\Models\Booking\BookingService;
use App\Models\Booking\Slot;
use App\Models\User;
use App\Preconditions\Booking\EnsureSlotTimeIsFree;
use App\Services\Booking\PriceCalculator;
use DomainException;
use Illuminate\Database\Connection;

/**
 * Создание записи оператором из панели (ФТ-18): снимок цены пересчитывается
 * сервером (сумма клиентом не передаётся). Запись ровно на начало часа занимает час —
 * слот закрывается и привязывается; закрытие — не барьер: чужую привязку не перезаписываем (ФТ-16).
 */
final readonly class CreateAdminBooking
{
    public function __construct(
        private Connection $connection,
        private PriceCalculator $priceCalculator,
        private EnsureSlotTimeIsFree $ensureSlotTimeIsFree,
    ) {}

    public function execute(CreateAdminBookingInput $input): Booking
    {
        return $this->connection->transaction(function () use ($input): Booking {
            $slot = Slot::query()->whereKey($input->slotId)->lockForUpdate()->first();

            if ($slot === null) {
                throw new DomainException('Слот не найден', 422);
            }

            // Под блокировкой слота: две записи на одно время не разойдутся гонкой
            $this->ensureSlotTimeIsFree->ensure($slot->id, $input->startTime);

            $user = User::query()->firstOrCreate(
                ['phone' => $input->phone],
                ['name' => $input->name],
            );

            $booking = Booking::create([
                'user_id' => $user->id,
                'slot_id' => $slot->id,
                'start_time' => $input->startTime,
                'status' => BookingStatus::Confirmed,
                'source' => BookingSource::Admin,
                'operator_id' => $input->operator->id,
                'radius' => $input->radius,
                'car_type' => $input->carType,
                'plate' => $input->plate,
                'total_price' => 0, // пересчитается ниже по составу
            ]);

            $services = BookingService::query()
                ->whereIn('id', array_keys($input->quantities))
                ->where('is_active', true)
                ->get();

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

            // Запись ровно на начало часа занимает час; чужую привязку не перезаписываем (ФТ-16)
            if ($slot->startsHour($input->startTime) && ! $slot->is_closed) {
                $slot->update(['is_closed' => true, 'booking_id' => $booking->id]);
            }

            return $booking;
        });
    }
}
