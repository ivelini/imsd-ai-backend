<?php

namespace App\Actions\Catalog;

use App\DTOs\Catalog\PopulateCatalogPricesInput;
use App\Models\Catalog\MarkupRule\WarehouseMarkupRule;
use App\Models\Catalog\Warehouse\Stock;
use App\Models\Delivery\CatalogPrice;
use App\Models\Delivery\City;
use App\Services\Catalog\PriceCalculator;
use Illuminate\Support\Collection;

/** Пересчитывает catalog_prices для всех stocks × все cities с учётом наценок. */
final readonly class PopulateCatalogPrices
{
    public function __construct(
        private PriceCalculator $priceCalculator,
    ) {}

    public function execute(PopulateCatalogPricesInput $input): void
    {
        $stocks = Stock::with('warehouse')->get();
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

                foreach ($cityIds as $cityId) {
                    $records[] = [
                        'stock_id' => $stock->id,
                        'city_id' => $cityId,
                        'price' => $finalPrice,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }
            }

            foreach (array_chunk($records, 500) as $chunk) {
                CatalogPrice::upsert($chunk, ['stock_id', 'city_id'], ['price', 'updated_at']);
            }
        }
    }
}
