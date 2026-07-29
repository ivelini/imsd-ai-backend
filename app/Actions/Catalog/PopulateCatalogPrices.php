<?php

namespace App\Actions\Catalog;

use App\DTOs\Catalog\PopulateCatalogPricesInput;
use App\Models\Catalog\Stock;
use App\Models\Catalog\WarehouseMarkupRule;
use App\Models\Delivery\CatalogPrice;
use App\Models\Delivery\City;
use Illuminate\Support\Facades\DB;

/** Пересчитывает catalog_prices для всех stocks × все cities с учётом наценок. */
final readonly class PopulateCatalogPrices
{
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

                $finalPrice = $this->calculateFinalPrice($stock);

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

    private function calculateFinalPrice(Stock $stock): float
    {
        $rule = WarehouseMarkupRule::where('warehouse_id', $stock->warehouse_id)
            ->where('price_from', '<=', $stock->purchase_price)
            ->where('price_to', '>=', $stock->purchase_price)
            ->orderBy('price_from')
            ->orderBy('price_to')
            ->first();

        if ($rule === null) {
            return (float) $stock->purchase_price;
        }

        return round((float) $stock->purchase_price * $rule->coefficient, 2);
    }
}
