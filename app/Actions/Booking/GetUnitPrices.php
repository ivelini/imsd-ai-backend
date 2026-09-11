<?php

namespace App\Actions\Booking;

use App\Enums\Booking\CarType;
use App\Models\Booking\BookingService;
use App\Services\Booking\PriceCalculator;

/**
 * Цены за единицу по всему каталогу при выбранных параметрах авто —
 * единый расчёт для карточек услуг шага выбора.
 *
 * @return array<int, int> service_id => цена за единицу (копейки)
 */
final readonly class GetUnitPrices
{
    public function __construct(private PriceCalculator $priceCalculator) {}

    public function execute(int $radius, CarType $carType): array
    {
        $catalog = BookingService::query()->where('is_active', true)->orderBy('id')->get();
        $quantities = $catalog->pluck('id')->mapWithKeys(fn (int $id): array => [$id => 1])->all();

        $unitPrices = [];
        foreach ($this->priceCalculator->calculate($catalog, $radius, $carType, $quantities)->lines as $line) {
            $unitPrices[$line->service->id] = $line->unitPrice->toKopecks();
        }

        return $unitPrices;
    }
}
