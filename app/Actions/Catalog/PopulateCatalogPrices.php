<?php

namespace App\Actions\Catalog;

use App\DTOs\Catalog\PopulateCatalogPricesInput;
use App\Models\Catalog\MarkupRule\WarehouseMarkupRule;
use App\Models\Catalog\Warehouse\Stock;
use App\Models\Delivery\CatalogPrice;
use App\Models\Delivery\City;
use App\Models\Delivery\CityDeliveryTime;
use App\Models\Delivery\CityPriceRule;
use App\Models\Delivery\DeliverySchedule;
use App\Services\Catalog\DeliveryTimeCalculator;
use App\Services\Catalog\MarkupRuleMatcher;
use App\Services\Catalog\PriceCalculator;
use Illuminate\Support\Collection;

/** Пересчёт catalog_prices: наценки склада + города, стабильный диапазон доставки. */
final readonly class PopulateCatalogPrices
{
    public function __construct(
        private PriceCalculator $priceCalculator,
    ) {}

    public function execute(PopulateCatalogPricesInput $input): void
    {
        $stocks = Stock::query()
            ->when($input->stockIds !== null, fn ($query) => $query->whereIn('id', $input->stockIds))
            ->get();
        $cityIds = City::pluck('id');

        // Правила загружаются один раз и сериализуются в массивы — сервис не знает о БД
        /** @var Collection<array-key, Collection<int, array<string, int|float>>> $allRules */
        $allRules = WarehouseMarkupRule::all()
            ->groupBy('warehouse_id')
            ->map(fn (Collection $group) => $group->map(fn (WarehouseMarkupRule $r) => [
                'price_from' => $r->price_from,
                'price_to' => $r->price_to,
                'coefficient' => $r->coefficient,
            ])->values());

        // Наценки города (стоимость доставки до города) — как и складские, в память один раз
        /** @var Collection<array-key, Collection<int, array<string, int|float>>> $cityRulesByCity */
        $cityRulesByCity = CityPriceRule::all()
            ->groupBy('city_id')
            ->map(fn (Collection $group) => $group->map(fn (CityPriceRule $r) => [
                'price_from' => (float) $r->price_from,
                'price_to' => (float) $r->price_to,
                'markup' => (float) $r->markup,
            ])->values());

        $schedules = DeliverySchedule::all()->groupBy('warehouse_id');
        $cityDeliveryDays = CityDeliveryTime::pluck('delivery_days', 'city_id');

        /** @var array<int, array{min: int, max: int}|null> $deliveryByWarehouse */
        $deliveryByWarehouse = [];

        foreach ($stocks->chunk(50) as $stocksChunk) {
            $records = [];

            foreach ($stocksChunk as $stock) {
                if ($stock->purchase_price === null) {
                    continue;
                }

                $finalPrice = $this->priceCalculator->calculateFinalPrice(
                    (float) $stock->purchase_price,
                    $stock->warehouse_id,
                    $allRules,
                );

                $warehouseId = $stock->warehouse_id;
                if (! array_key_exists($warehouseId, $deliveryByWarehouse)) {
                    $deliveryByWarehouse[$warehouseId] = DeliveryTimeCalculator::deliveryRange(
                        $schedules->get($warehouseId, collect()),
                    );
                }
                $delivery = $deliveryByWarehouse[$warehouseId];

                foreach ($cityIds as $cityId) {
                    $deliveryDays = $cityDeliveryDays->get($cityId);
                    $markup = MarkupRuleMatcher::match(
                        $finalPrice,
                        $cityRulesByCity->get($cityId, collect())->all(),
                    );
                    $price = $markup !== null
                        ? round($finalPrice + (float) $markup['markup'], 2)
                        : $finalPrice;

                    $records[] = [
                        'stock_id' => $stock->id,
                        'city_id' => $cityId,
                        'price' => $price,
                        'delivery_min' => $delivery !== null && $deliveryDays !== null
                            ? $delivery['min'] + $deliveryDays
                            : null,
                        'delivery_max' => $delivery !== null && $deliveryDays !== null
                            ? $delivery['max'] + $deliveryDays
                            : null,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }
            }

            foreach (array_chunk($records, 500) as $chunk) {
                CatalogPrice::upsert(
                    $chunk,
                    ['stock_id', 'city_id'],
                    ['price', 'delivery_min', 'delivery_max', 'updated_at'],
                );
            }
        }
    }
}
