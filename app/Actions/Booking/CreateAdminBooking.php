<?php

namespace App\Actions\Booking;

use App\DTOs\Booking\CreateAdminBookingInput;
use App\Enums\Booking\BookingSource;
use App\Enums\Booking\BookingStatus;
use App\Models\Booking\Booking;
use App\Models\Booking\BookingItem;
use App\Models\Booking\Slot;
use App\Models\User;
use App\Preconditions\Booking\EnsureSlotTimeIsFree;
use DomainException;
use Illuminate\Database\Connection;

/**
 * Создание записи оператором из панели (ФТ-18): состав и цены строк — из формы
 * (оператор правит их так же, как на правке), итог считается по сохранённым строкам.
 * Запись ровно на начало часа занимает час — слот закрывается и привязывается;
 * закрытие — не барьер: чужую привязку не перезаписываем (ФТ-16).
 */
final readonly class CreateAdminBooking
{
    public function __construct(
        private Connection $connection,
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

            // ФИО вводит оператор при каждой записи: карточка клиента следует за вводом. Пустое
            // отчество прежнее значение не затирает — очистить его можно на правке записи
            $user = User::query()->updateOrCreate(
                ['phone' => $input->phone],
                array_filter(
                    ['name' => $input->name, 'surname' => $input->surname, 'patronymic' => $input->patronymic],
                    fn (?string $part): bool => filled($part),
                ),
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

            foreach ($input->items as $item) {
                BookingItem::create([
                    'booking_id' => $booking->id,
                    'service_id' => $item->serviceId,
                    'price' => $item->price, // снимок: цена за единицу, как её поставил оператор
                    'quantity' => $item->quantity,
                ]);
            }

            $booking->update(['total_price' => $booking->itemsTotal()]);

            // Запись ровно на начало часа занимает час; чужую привязку не перезаписываем (ФТ-16)
            if ($slot->startsHour($input->startTime) && ! $slot->is_closed) {
                $slot->update(['is_closed' => true, 'booking_id' => $booking->id]);
            }

            return $booking;
        });
    }
}
