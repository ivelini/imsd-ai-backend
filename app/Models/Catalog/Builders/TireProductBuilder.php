<?php

namespace App\Models\Catalog\Builders;

use App\Enums\Catalog\DeliveryDaysType;
use App\Models\Catalog\Brand\Brand;
use App\Models\Catalog\Country\Country;
use App\Models\Catalog\Tire\TireProduct;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

/** Кастомный Builder для TireProduct — фильтры каталога.
 *
 * @extends Builder<TireProduct>
 */
class TireProductBuilder extends Builder
{
    public function search(string $search): self
    {
        $q = '%'.$search.'%';
        $this->where(function (Builder $query) use ($q) {
            $query->where('tire_products.name', 'like', $q)
                ->orWhere('tire_products.ean', 'like', $q)
                ->orWhereHas('model', fn (Builder $m) => $m->where('name', 'like', $q));
        });

        return $this;
    }

    public function byBrand(int $brandId): self
    {
        $this->where('tire_products.brand_id', $brandId);

        return $this;
    }

    public function byModel(int $modelId): self
    {
        $this->where('tire_products.model_id', $modelId);

        return $this;
    }

    public function published(bool $published): self
    {
        $this->where('tire_products.is_published', $published);

        return $this;
    }

    public function bySeason(string $season): self
    {
        $this->where('tire_products.season', $season);

        return $this;
    }

    public function studded(bool $studded): self
    {
        $this->where('tire_products.is_studded', $studded);

        return $this;
    }

    public function runflat(bool $runflat): self
    {
        $this->where('tire_products.is_runflat', $runflat);

        return $this;
    }

    public function xl(bool $xl): self
    {
        $this->where('tire_products.is_xl', $xl);

        return $this;
    }

    public function byWidths(array $widths): self
    {
        $this->whereIn('tire_products.width', $widths);

        return $this;
    }

    public function byProfiles(array $profiles): self
    {
        $this->whereIn('tire_products.profile', $profiles);

        return $this;
    }

    public function byDiameters(array $diameters): self
    {
        $this->whereIn('tire_products.diameter', $diameters);

        return $this;
    }

    public function byLoadIndexes(array $indexes): self
    {
        $this->whereIn('tire_products.load_index', $indexes);

        return $this;
    }

    public function bySpeedIndexes(array $indexes): self
    {
        $this->whereIn('tire_products.speed_index', $indexes);

        return $this;
    }

    public function byYears(array $years): self
    {
        $this->whereIn('tire_products.year', $years);

        return $this;
    }

    public function bestseller(bool $bestseller): self
    {
        $this->where('tire_products.is_bestseller', $bestseller);

        return $this;
    }

    public function isNew(bool $isNew): self
    {
        $this->where('tire_products.is_new', $isNew);

        return $this;
    }

    public function byBrandSlug(string $slug): self
    {
        $this->where('tire_products.brand_id', Brand::query()->where('slug', $slug)->value('id'));

        return $this;
    }

    public function byCountrySlug(string $slug): self
    {
        $this->where('tire_products.country_id', Country::query()->where('slug', $slug)->value('id'));

        return $this;
    }

    public function byDeliveryRange(int $cityId, DeliveryDaysType $type): self
    {
        $this->whereIn('tire_products.id', $this->productIdsByDeliveryRange($cityId, $type));

        return $this;
    }

    public function byPriceRange(int $cityId, ?float $min, ?float $max): self
    {
        $this->whereIn('tire_products.id', $this->productIdsByPriceRange($cityId, $min ?? 0.0, $max ?? 999999999.0));

        return $this;
    }

    /**
     * Фильтры публичного каталога (общие для фасетов и листинга).
     *
     * @param  array<string, mixed>  $filters  Валидированные query-параметры фильтра
     */
    public function byCatalogFilters(int $cityId, array $filters, bool $requireCityPrice = false): self
    {
        if (! empty($filters['width'])) {
            $this->byWidths(array_map('intval', $filters['width']));
        }

        if (! empty($filters['profile'])) {
            $this->byProfiles(array_map('intval', $filters['profile']));
        }

        if (! empty($filters['diameter'])) {
            $this->byDiameters($filters['diameter']);
        }

        if (! empty($filters['season'])) {
            $this->bySeason($filters['season']);
        }

        if (! empty($filters['studded'])) {
            $this->studded($filters['studded'] === 'studded');
        }

        if (! empty($filters['brand'])) {
            $this->byBrandSlug($filters['brand']);
        }

        if (! empty($filters['country'])) {
            $this->byCountrySlug($filters['country']);
        }

        if (! empty($filters['delivery'])) {
            $this->byDeliveryRange($cityId, DeliveryDaysType::from($filters['delivery']));
        }

        // requireCityPrice — листинг не показывает товары без цены города (пустой диапазон = «есть цена»)
        if (isset($filters['price_min']) || isset($filters['price_max']) || $requireCityPrice) {
            $this->byPriceRange(
                $cityId,
                isset($filters['price_min']) ? (float) $filters['price_min'] : null,
                isset($filters['price_max']) ? (float) $filters['price_max'] : null,
            );
        }

        return $this;
    }

    /** @return list<int> Товары, чей min delivery_min по стокам города попадает в бакет. */
    private function productIdsByDeliveryRange(int $cityId, DeliveryDaysType $type): array
    {
        return DB::table('stocks')
            ->join('catalog_prices', 'catalog_prices.stock_id', '=', 'stocks.id')
            ->where('stocks.stockable_type', $this->morphClass())
            ->where('stocks.quantity', '>', 0)
            ->where('catalog_prices.city_id', $cityId)
            ->whereNotNull('catalog_prices.delivery_min')
            ->groupBy('stocks.stockable_id')
            ->havingRaw('MIN(catalog_prices.delivery_min) BETWEEN ? AND ?', [$type->minDays(), $type->maxDays() ?? 999999])
            ->selectRaw('stocks.stockable_id as stockable_id')
            ->pluck('stockable_id')
            ->all();
    }

    /** @return list<int> Товары, чей min price по стокам города попадает в диапазон. */
    private function productIdsByPriceRange(int $cityId, float $min, float $max): array
    {
        return DB::table('stocks')
            ->join('catalog_prices', 'catalog_prices.stock_id', '=', 'stocks.id')
            ->where('stocks.stockable_type', $this->morphClass())
            ->where('stocks.quantity', '>', 0)
            ->where('catalog_prices.city_id', $cityId)
            ->whereNotNull('catalog_prices.price')
            ->groupBy('stocks.stockable_id')
            // CAST: у выражения MIN() нет affinity, иначе sqlite сравнивает INTEGER-значение с TEXT-привязкой как «integer < text»
            ->havingRaw('CAST(MIN(catalog_prices.price) AS NUMERIC) BETWEEN ? AND ?', [$min, $max])
            ->selectRaw('stocks.stockable_id as stockable_id')
            ->pluck('stockable_id')
            ->all();
    }

    private function morphClass(): string
    {
        return $this->getModel()->getMorphClass();
    }
}
