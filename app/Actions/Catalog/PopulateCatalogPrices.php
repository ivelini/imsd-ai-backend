<?php

namespace App\Actions\Catalog;

use App\DTOs\Catalog\PopulateCatalogPricesInput;
use App\Models\Catalog\Stock;
use App\Models\Delivery\CatalogPrice;
use App\Models\Delivery\City;
use App\Services\Catalog\PriceCalculator;
use Illuminate\Support\Facades\DB;

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

        foreach ($stocks->chunk(50) as $stocksChunk) {
            $records = [];

            foreach ($stocksChunk as $stock) {
                if ($stock->purchase_price === null) {
                    continue;
                }

                $finalPrice = $this->priceCalculator->calculateFinalPrice(
                    (float) $stock->purchase_price,
                    $stock->warehouse_id,
                );

                foreach ($cityIds as $cityId) {
                    $records[] = [
                        'stock_id' => $stock->id,
                        'city_id' => $cityId,
                        'price' => $finalPrice,
                        'created_at' => DB::raw('now()'),
                        'updated_at' => DB::raw('now()'),
                    ];
                }
            }

            foreach (array_chunk($records, 500) as $chunk) {
                CatalogPrice::upsert($chunk, ['stock_id', 'city_id'], ['price', 'updated_at']);
            }
        }
    }
}
