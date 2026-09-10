<?php

namespace App\Actions\Promotion;

use App\Actions\Catalog\PopulateCatalogPrices;
use App\DTOs\Catalog\PopulateCatalogPricesInput;
use App\Models\Catalog\Promotion\Promotion;
use App\Models\Catalog\Warehouse\Stock;
use Illuminate\Database\Eloquent\Relations\Relation;

/** Пересчитать цены товаров, затронутых акциями (границы действия и правки из панели). */
final readonly class RecalculatePromotedPrices
{
    public function __construct(
        private PopulateCatalogPrices $populateCatalogPrices,
    ) {}

    /** Пересчёт по конкретным акциям — после сохранения или удаления. */
    public function forPromotions(Promotion ...$promotions): void
    {
        $stockIds = [];

        foreach ($promotions as $promotion) {
            $stockIds = [...$stockIds, ...$this->stockIdsFor($promotion)];
        }

        $this->recalculate($stockIds);
    }

    /**
     * Пересчёт всех товаров, пока есть хоть одна акция — плановая задача
     * закрывает границы интервала (начало/окончание без правок из панели).
     */
    public function forAnyPromotedProducts(): void
    {
        if (Promotion::query()->exists()) {
            $this->recalculate(Stock::query()->pluck('id')->all());
        }
    }

    /** @return list<int> */
    private function stockIdsFor(Promotion $promotion): array
    {
        // Каталоговая акция затрагивает все остатки
        if ($promotion->promotable_type === null) {
            return Stock::query()->pluck('id')->all();
        }

        if ($promotion->promotable_type === 'brand') {
            return $this->stockIdsOfBrand((int) $promotion->promotable_id);
        }

        return Stock::query()
            ->where('stockable_type', $promotion->promotable_type)
            ->where('stockable_id', $promotion->promotable_id)
            ->pluck('id')
            ->all();
    }

    /** Бренд бывает шинным и дискным — берём товары обоих морф-типов. */
    private function stockIdsOfBrand(int $brandId): array
    {
        $stockIds = [];

        foreach (['tire', 'wheel'] as $productType) {
            $model = Relation::getMorphedModel($productType);

            if ($model === null) {
                continue;
            }

            $productIds = $model::query()->where('brand_id', $brandId)->pluck('id')->all();

            if ($productIds === []) {
                continue;
            }

            $stockIds = [...$stockIds, ...Stock::query()
                ->where('stockable_type', $productType)
                ->whereIn('stockable_id', $productIds)
                ->pluck('id')
                ->all()];
        }

        return $stockIds;
    }

    /** @param  list<int>  $stockIds */
    private function recalculate(array $stockIds): void
    {
        if ($stockIds === []) {
            return;
        }

        $this->populateCatalogPrices->execute(new PopulateCatalogPricesInput(stockIds: $stockIds));
    }
}
