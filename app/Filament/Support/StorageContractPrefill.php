<?php

namespace App\Filament\Support;

use App\Enums\Booking\ServiceCategory;
use App\Models\Booking\Booking;
use App\Models\Booking\BookingItem;
use App\Models\Booking\BookingService;

/**
 * Мост «запись → договор хранения»: показывает ли запись хранение (по категории услуги) и собирает
 * адрес формы договора — клиент, срок «с» по дате слота и стоимость строки хранения.
 */
final class StorageContractPrefill
{
    private function __construct() {}

    /**
     * Есть ли в составе услуга категории «Хранение»: название услуги правится в справочнике,
     * категория — нет, поэтому кнопку показывает именно она.
     *
     * @param  array<int|string, array<string, mixed>>  $items  состояние репитера «Услуги»
     */
    public static function hasStorageService(array $items): bool
    {
        $ids = collect($items)
            ->pluck('service_id')
            ->filter(fn (mixed $id): bool => is_numeric($id))
            ->map(fn (mixed $id): int => (int) $id);

        return $ids->isNotEmpty()
            && BookingService::query()->whereIn('id', $ids)->where('category', ServiceCategory::Storage)->exists();
    }

    /**
     * Параметры адреса формы договора: незаполненное (нет цены у строки) в адрес не попадает —
     * форма сама покажет пустое поле.
     *
     * @return array<string, string|int>
     */
    public static function paramsFor(Booking $booking): array
    {
        $line = $booking->items()->with('service')->get()
            ->first(fn (BookingItem $item): bool => $item->service?->category === ServiceCategory::Storage);

        return array_filter([
            'user_id' => $booking->user_id,
            'starts_on' => $booking->slot?->date?->format('Y-m-d'),
            'price' => $line === null ? null : (string) $line->price->multiply($line->quantity)->toRubles(),
        ], fn (mixed $value): bool => $value !== null);
    }
}
