<?php

namespace App\Actions\Booking;

use App\DTOs\Booking\BookingItemInput;
use App\DTOs\Booking\UpdateAdminBookingInput;
use App\Models\Booking\Booking;
use App\Models\Booking\Slot;
use App\Preconditions\Booking\EnsureSlotTimeIsFree;
use App\ValueObjects\Money;
use DomainException;
use Illuminate\Database\Connection;

/**
 * Правка записи оператором (ФТ-19): снимок полей, время и состав обновляются вместе,
 * итоговая стоимость считается по сохранённым строкам (цена × количество).
 * Цена строки приходит из формы как есть — ручную правку оператора прайс не переписывает.
 */
final readonly class UpdateAdminBooking
{
    public function __construct(
        private Connection $connection,
        private EnsureSlotTimeIsFree $ensureSlotTimeIsFree,
    ) {}

    public function execute(UpdateAdminBookingInput $input): void
    {
        $this->connection->transaction(function () use ($input): void {
            $booking = $input->booking;
            $slot = Slot::query()->whereKey($booking->slot_id)->lockForUpdate()->first();

            if ($slot === null) {
                throw new DomainException('Слот записи не найден', 422);
            }

            // Своё же время записи занятым не считается (exceptBookingId) — иначе не сохранить без смены времени
            $this->ensureSlotTimeIsFree->ensure($slot->id, $input->startTime, $booking->id);

            $booking->update([
                'plate' => $input->plate,
                'radius' => $input->radius,
                'car_type' => $input->carType,
                'status' => $input->status,
                'cancel_reason' => $input->cancelReason,
                'start_time' => $input->startTime,
            ]);

            $booking->items()
                ->whereNotIn('service_id', array_map(fn (BookingItemInput $item): int => $item->serviceId, $input->items))
                ->delete();

            foreach ($input->items as $item) {
                // Синхронизация по услуге: строка сохраняет id, повторное сохранение её не пересоздаёт
                $booking->items()->updateOrCreate(
                    ['service_id' => $item->serviceId],
                    ['price' => $item->price, 'quantity' => $item->quantity],
                );
            }

            $booking->update(['total_price' => $this->totalOf($booking)]);

            $this->syncSlotClosure($slot, $booking, $input->startTime);
        });
    }

    /** Час занимает только запись ровно на его начало; уехала внутрь часа — час освобождается. */
    private function syncSlotClosure(Slot $slot, Booking $booking, string $startTime): void
    {
        if ($slot->startsHour($startTime)) {
            if (! $slot->is_closed) {
                $slot->update(['is_closed' => true, 'booking_id' => $booking->id]);
            }

            return;
        }

        // Снимаем только своё закрытие: чужую привязку и ручную причину не трогаем
        if ($slot->booking_id === $booking->id && $slot->close_reason === null) {
            $slot->update(['is_closed' => false, 'booking_id' => null]);
        }
    }

    /** Итог записи: сумма строк «цена за единицу × количество». */
    private function totalOf(Booking $booking): Money
    {
        $total = Money::fromKopecks(0);

        foreach ($booking->items()->get() as $item) {
            $total = $total->add($item->price->multiply($item->quantity));
        }

        return $total;
    }
}
