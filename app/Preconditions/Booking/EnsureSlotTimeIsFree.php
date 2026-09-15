<?php

namespace App\Preconditions\Booking;

use App\Enums\Booking\BookingStatus;
use App\Models\Booking\Booking;
use DomainException;
use Illuminate\Database\Eloquent\Builder;

/**
 * Время в слоте свободно: две записи на одно время невозможны. Отменённые время не держат —
 * их время снова доступно. Проверка вызывается под блокировкой строки слота (гонка).
 */
final readonly class EnsureSlotTimeIsFree
{
    /** @param int|null $exceptBookingId правимая запись — сама себе времени не занимает */
    public function ensure(int $slotId, string $startTime, ?int $exceptBookingId = null): void
    {
        $isTaken = Booking::query()
            ->where('slot_id', $slotId)
            ->where('start_time', $startTime)
            ->where('status', '!=', BookingStatus::Cancelled->value)
            ->when($exceptBookingId !== null, fn (Builder $query): Builder => $query->whereKeyNot($exceptBookingId))
            ->exists();

        if ($isTaken) {
            throw new DomainException('На это время в слоте уже есть запись', 409);
        }
    }
}
