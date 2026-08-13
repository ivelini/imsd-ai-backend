<?php

namespace App\Actions\Catalog;

use App\DTOs\Catalog\PopulateCatalogPricesInput;
use App\DTOs\Catalog\RecalcContext;
use App\Models\Catalog\MarkupRule\WarehouseMarkupRule;
use App\Models\Catalog\Warehouse\Stock;
use App\Models\Delivery\CatalogPrice;
use App\Models\Delivery\City;
use App\Models\Delivery\CityDeliveryTime;
use App\Models\Delivery\CityPriceRule;
use App\Models\Delivery\DeliverySchedule;
use App\Services\Catalog\MarkupRuleMatcher;
use App\Services\Catalog\PriceCalculator;
use App\Services\Delivery\DeliveryTimeCalculator;
use Illuminate\Support\Collection;

/** Пересчёт catalog_prices: наценки склада + города, стабильный диапазон доставки. */
final readonly class PopulateCatalogPrices
{
    public function __construct(
        private PriceCalculator $priceCalculator,
    ) {}

    public function execute(PopulateCatalogPricesInput $input): void
    {
        $stocks = $this->loadStocks($input->stockIds);
        $cityIds = City::pluck('id');

        $recalc = new RecalcContext(
            warehouseRules: $this->loadWarehouseRules(),
            cityRules: $this->loadCityRules(),
            deliveryByWarehouse: $this->loadDeliveryByWarehouse(),
            cityDeliveryDays: CityDeliveryTime::pluck('delivery_days', 'city_id'),
        );

        foreach ($stocks->chunk(50) as $stocksChunk) {
            $this->recalculateChunk($stocksChunk, $cityIds, $recalc);
        }
    }

    /** @param  int[]|null  $stockIds */
    private function loadStocks(?array $stockIds): Collection
    {
        return Stock::query()
            ->when($stockIds !== null, fn ($query) => $query->whereIn('id', $stockIds))
            ->get();
    }

    /** Правила склада сериализуются в массивы — сервис не знает о БД. */
    private function loadWarehouseRules(): Collection
    {
        return WarehouseMarkupRule::all()
            ->groupBy('warehouse_id')
            ->map(fn (Collection $group) => $group->map(fn (WarehouseMarkupRule $r) => [
                'price_from' => $r->price_from,
                'price_to' => $r->price_to,
                'coefficient' => $r->coefficient,
            ])->values());
    }

    /** Наценки города (стоимость доставки до города) — в том же формате, что складские. */
    private function loadCityRules(): Collection
    {
        return CityPriceRule::all()
            ->groupBy('city_id')
            ->map(fn (Collection $group) => $group->map(fn (CityPriceRule $r) => [
                'price_from' => (float) $r->price_from,
                'price_to' => (float) $r->price_to,
                'markup' => (float) $r->markup,
            ])->values());
    }

    /** Диапазон доставки каждого склада — один раз, до цикла по остаткам. */
    private function loadDeliveryByWarehouse(): Collection
    {
        return DeliverySchedule::all()
            ->groupBy('warehouse_id')
            ->map(fn (Collection $schedules) => DeliveryTimeCalculator::deliveryRange($schedules));
    }

    /** @param  Collection<int, Stock>  $stocksChunk */
    private function recalculateChunk(Collection $stocksChunk, Collection $cityIds, RecalcContext $recalc): void
    {
        $records = [];

        foreach ($stocksChunk as $stock) {
            if ($stock->purchase_price === null) {
                continue;
            }

            $records = [...$records, ...$this->recordsForStock($stock, $cityIds, $recalc)];
        }

        foreach (array_chunk($records, 500) as $chunk) {
            CatalogPrice::upsert(
                $chunk,
                ['stock_id', 'city_id'],
                ['price', 'delivery_min', 'delivery_max', 'updated_at'],
            );
        }
    }

    /** @return list<array<string, mixed>> */
    private function recordsForStock(Stock $stock, Collection $cityIds, RecalcContext $recalc): array
    {
        $finalPrice = $this->priceCalculator->calculateFinalPrice(
            (float) $stock->purchase_price,
            $stock->warehouse_id,
            $recalc->warehouseRules,
        );
        $delivery = $recalc->deliveryByWarehouse->get($stock->warehouse_id);

        $records = [];
        foreach ($cityIds as $cityId) {
            $deliveryDays = $recalc->cityDeliveryDays->get($cityId);
            $deliveryRange = $delivery !== null && $deliveryDays !== null
                ? ['min' => $delivery['min'] + $deliveryDays, 'max' => $delivery['max'] + $deliveryDays]
                : null;

            $records[] = [
                'stock_id' => $stock->id,
                'city_id' => $cityId,
                'price' => $this->priceForCity($finalPrice, $cityId, $recalc->cityRules),
                'delivery_min' => $deliveryRange['min'] ?? null,
                'delivery_max' => $deliveryRange['max'] ?? null,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        return $records;
    }

    /** @param  Collection<array-key, Collection<int, array<string, float>>>  $cityRules */
    private function priceForCity(float $finalPrice, int $cityId, Collection $cityRules): float
    {
        $markup = MarkupRuleMatcher::match($finalPrice, $cityRules->get($cityId, collect())->all());

        return $markup !== null
            ? round($finalPrice + $markup['markup'], 2)
            : $finalPrice;
    }
}
