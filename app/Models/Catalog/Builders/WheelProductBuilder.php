<?php

namespace App\Models\Catalog\Builders;

use App\Enums\Catalog\DeliveryDaysType;
use App\Models\Catalog\Brand\Brand;
use App\Models\Catalog\Country\Country;
use App\Models\Catalog\Wheel\WheelProduct;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

/** Кастомный Builder для WheelProduct — фильтры каталога.
 *
 * @extends Builder<WheelProduct>
 */
class WheelProductBuilder extends Builder
{
    public function search(string $search): self
    {
        $q = '%'.$search.'%';
        $this->where(function (Builder $query) use ($q) {
            $query->where('wheel_products.name', 'like', $q)
                ->orWhere('wheel_products.ean', 'like', $q)
                ->orWhereHas('model', fn (Builder $m) => $m->where('name', 'like', $q));
        });

        return $this;
    }

    public function byBrand(int $brandId): self
    {
        $this->where('wheel_products.brand_id', $brandId);

        return $this;
    }

    public function byModel(int $modelId): self
    {
        $this->where('wheel_products.model_id', $modelId);

        return $this;
    }

    public function published(bool $published): self
    {
        $this->where('wheel_products.is_published', $published);

        return $this;
    }

    public function byType(string $type): self
    {
        $this->where('wheel_products.type', $type);

        return $this;
    }

    public function byColor(string $color): self
    {
        $this->where('wheel_products.color', $color);

        return $this;
    }

    public function byWidths(array $widths): self
    {
        $this->whereIn('wheel_products.width', array_map($this->normalizeDecimal(...), $widths));

        return $this;
    }

    public function byDiameters(array $diameters): self
    {
        $this->whereIn('wheel_products.diameter', $diameters);

        return $this;
    }

    public function byPcds(array $pcds): self
    {
        $this->whereIn('wheel_products.pcd', $pcds);

        return $this;
    }

    public function byEts(array $ets): self
    {
        $this->whereIn('wheel_products.et', array_map($this->normalizeDecimal(...), $ets));

        return $this;
    }

    public function byHubDiameters(array $hubDiameters): self
    {
        $this->whereIn('wheel_products.hub_diameter', array_map($this->normalizeDecimal(...), $hubDiameters));

        return $this;
    }

    /** Приведение decimal-входа (int|float|string) к строке с одним знаком — сравнение со значениями БД. */
    private function normalizeDecimal(mixed $value): string
    {
        return number_format((float) $value, 1, '.', '');
    }

    public function bestseller(bool $bestseller): self
    {
        $this->where('wheel_products.is_bestseller', $bestseller);

        return $this;
    }

    public function isNew(bool $isNew): self
    {
        $this->where('wheel_products.is_new', $isNew);

        return $this;
    }

    /**
     * Фильтры публичного каталога дисков (общие для фасетов и листинга).
     *
     * @param  array<string, mixed>  $filters  Валидированные query-параметры фильтра
     */
    public function byCatalogFilters(int $cityId, array $filters, bool $requireCityPrice = false): self
    {
        if (! empty($filters['width'])) {
            $this->byWidths($filters['width']);
        }

        if (! empty($filters['diameter'])) {
            $this->byDiameters(array_map('intval', $filters['diameter']));
        }

        if (! empty($filters['pcd'])) {
            $this->byPcds($filters['pcd']);
        }

        if (! empty($filters['et'])) {
            $this->byEts($filters['et']);
        }

        if (! empty($filters['hub_diameter'])) {
            $this->byHubDiameters($filters['hub_diameter']);
        }

        if (! empty($filters['type'])) {
            $this->byType($filters['type']);
        }

        if (! empty($filters['color'])) {
            $this->byColor($filters['color']);
        }

        if (! empty($filters['brand'])) {
            $this->byBrandSlug($filters['brand']);
        }

        if (! empty($filters['country'])) {
            $this->byCountrySlug($filters['country']);
        }

        if (! empty($filters['delivery'])) {
            $this->byDeliveryRanges(
                $cityId,
                array_map(fn (string $value): DeliveryDaysType => DeliveryDaysType::from($value), $filters['delivery']),
            );
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

    /** @param  list<DeliveryDaysType>  $types  Бакеты доставки */
    private function byDeliveryRanges(int $cityId, array $types): self
    {
        $ids = [];
        foreach ($types as $type) {
            $ids = array_merge($ids, $this->productIdsByDeliveryRange($cityId, $type));
        }

        if ($ids !== []) {
            $this->whereIn('wheel_products.id', array_values(array_unique($ids)));
        } else {
            $this->whereRaw('1 = 0');
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
            ->havingRaw('MIN(catalog_prices.delivery_min) BETWEEN ? AND ?', [$type->minDays(), $type->maxDays() ?? 999])
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

    private function byBrandSlug(string $slug): self
    {
        $brandId = Brand::query()->where('slug', $slug)->value('id');

        if ($brandId !== null) {
            $this->byBrand((int) $brandId);
        } else {
            $this->whereRaw('1 = 0');
        }

        return $this;
    }

    private function byCountrySlug(string $slug): self
    {
        $countryId = Country::query()->where('slug', $slug)->value('id');

        if ($countryId !== null) {
            $this->where('wheel_products.country_id', $countryId);
        } else {
            $this->whereRaw('1 = 0');
        }

        return $this;
    }

    private function byPriceRange(int $cityId, ?float $min, ?float $max): self
    {
        $ids = $this->productIdsByPriceRange($cityId, $min ?? 0.0, $max ?? 999999999.0);

        if ($ids !== []) {
            $this->whereIn('wheel_products.id', $ids);
        } else {
            $this->whereRaw('1 = 0');
        }

        return $this;
    }

    private function morphClass(): string
    {
        return $this->getModel()->getMorphClass();
    }
}
