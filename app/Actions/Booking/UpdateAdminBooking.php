<?php

namespace App\Actions\Booking;

use App\DTOs\Booking\BookingItemInput;
use App\DTOs\Booking\UpdateAdminBookingInput;
use App\Models\Booking\Booking;
use App\ValueObjects\Money;
use Illuminate\Database\Connection;

/**
 * Правка записи оператором (ФТ-19): снимок полей и состав обновляются вместе,
 * итоговая стоимость считается по сохранённым строкам (цена × количество).
 * Цена строки приходит из формы как есть — ручную правку оператора прайс не переписывает.
 */
final readonly class UpdateAdminBooking
{
    public function __construct(private Connection $connection) {}

    public function execute(UpdateAdminBookingInput $input): void
    {
        $this->connection->transaction(function () use ($input): void {
            $booking = $input->booking;

            $booking->update([
                'plate' => $input->plate,
                'radius' => $input->radius,
                'car_type' => $input->carType,
                'status' => $input->status,
                'cancel_reason' => $input->cancelReason,
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
        });
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
