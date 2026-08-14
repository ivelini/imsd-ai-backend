<?php

namespace App\Actions\Catalog\Tire;

use App\Enums\Catalog\DeliveryDaysType;
use App\Enums\Catalog\Season;
use App\Models\Catalog\Brand\Brand;
use App\Models\Catalog\Builders\TireProductBuilder;
use App\Models\Catalog\Country\Country;
use App\Models\Catalog\Tire\TireProduct;
use App\Services\Catalog\Tire\TireFacetAssembler;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Фасетные значения фильтра каталога шин для города по умолчанию.
 *
 * БД-обвязка: читает сырые значения по суженному активными фильтрами множеству,
 * сборку фасетов делегирует TireFacetAssembler.
 */
final readonly class GetTireFilterValues
{
    /**
     * @param  array<string, mixed>  $filters  Валидированные query-параметры активного фильтра
     */
    public function execute(int $cityId, array $filters): array
    {
        $published = TireProduct::query()
            ->published(true)
            ->whereHas('stocks', fn (Builder $q) => $q->where('quantity', '>', 0));

        $this->applyFilters($published, $cityId, $filters);

        return [
            'width' => TireFacetAssembler::dimension($this->distinct(clone $published, 'width')),
            'profile' => TireFacetAssembler::dimension($this->distinct(clone $published, 'profile')),
            'diameter' => TireFacetAssembler::diameter($this->distinct(clone $published, 'diameter')),
            'season' => TireFacetAssembler::season($this->seasonValues(clone $published)),
            'studded' => TireFacetAssembler::studded($this->distinct(clone $published, 'is_studded')),
            'brand' => TireFacetAssembler::named($this->brandNames(clone $published)),
            'country' => TireFacetAssembler::named($this->countryNames(clone $published)),
            'delivery' => TireFacetAssembler::delivery($this->minDeliveryDays(clone $published, $cityId)),
            'price' => $this->priceRange(clone $published, $cityId),
        ];
    }

    /** @param  array<string, mixed>  $filters */
    private function applyFilters(TireProductBuilder $query, int $cityId, array $filters): void
    {
        if (! empty($filters['width'])) {
            $query->byWidths(array_map('intval', $filters['width']));
        }

        if (! empty($filters['profile'])) {
            $query->byProfiles(array_map('intval', $filters['profile']));
        }

        if (! empty($filters['diameter'])) {
            $query->byDiameters($filters['diameter']);
        }

        if (! empty($filters['season'])) {
            $query->bySeason($filters['season']);
        }

        if (! empty($filters['studded'])) {
            $query->studded($filters['studded'] === 'studded');
        }

        if (! empty($filters['brand'])) {
            $query->byBrand((int) Brand::query()->where('slug', $filters['brand'])->value('id'));
        }

        if (! empty($filters['country'])) {
            $query->where('tire_products.country_id', (int) Country::query()->where('slug', $filters['country'])->value('id'));
        }

        if (! empty($filters['delivery'])) {
            $query->whereIn('tire_products.id', $this->productIdsByDeliveryRange($cityId, DeliveryDaysType::from($filters['delivery'])));
        }

        if (isset($filters['price_min']) || isset($filters['price_max'])) {
            $query->whereIn('tire_products.id', $this->productIdsByPriceRange($cityId, $filters));
        }
    }

    /** Товары, чей min delivery_min по стокам города попадает в бакет. */
    private function productIdsByDeliveryRange(int $cityId, DeliveryDaysType $type): Collection
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
            ->pluck('stockable_id');
    }

    /** Товары, чей min price по стокам города попадает в диапазон. */
    private function productIdsByPriceRange(int $cityId, array $filters): Collection
    {
        $min = isset($filters['price_min']) ? (float) $filters['price_min'] : 0.0;
        $max = isset($filters['price_max']) ? (float) $filters['price_max'] : 999999999.0;

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
            ->pluck('stockable_id');
    }

    /** @return list<int|string|bool> */
    private function distinct(Builder $query, string $column): array
    {
        return $query->whereNotNull($column)->distinct()->pluck($column)->all();
    }

    /** @return list<string> */
    private function seasonValues(Builder $query): array
    {
        // pluck() применяет касты модели — season приходит enum-объектами
        return $query->whereNotNull('season')->distinct()->pluck('season')
            ->map(fn (Season $season): string => $season->value)
            ->all();
    }

    /** @return list<array{slug: string|null, name: string}> */
    private function brandNames(Builder $query): array
    {
        $ids = $query->whereNotNull('brand_id')->distinct()->pluck('brand_id');

        return Brand::query()->whereIn('id', $ids)
            ->get(['slug', 'name'])
            ->map(fn (Brand $brand): array => ['slug' => $brand->slug, 'name' => $brand->name])
            ->values()
            ->all();
    }

    /** @return list<array{slug: string|null, name: string}> */
    private function countryNames(Builder $query): array
    {
        $ids = $query->whereNotNull('country_id')->distinct()->pluck('country_id');

        return Country::query()->whereIn('id', $ids)
            ->get(['name', 'slug'])
            ->map(fn (Country $country): array => ['slug' => $country->slug, 'name' => $country->name])
            ->values()
            ->all();
    }

    /** @return list<int> */
    private function minDeliveryDays(Builder $products, int $cityId): array
    {
        return DB::table('stocks')
            ->join('catalog_prices', 'catalog_prices.stock_id', '=', 'stocks.id')
            ->where('stocks.stockable_type', $this->morphClass())
            ->where('stocks.quantity', '>', 0)
            ->where('catalog_prices.city_id', $cityId)
            ->whereNotNull('catalog_prices.delivery_min')
            ->whereIn('stocks.stockable_id', $products->select('id'))
            ->groupBy('stocks.stockable_id')
            ->selectRaw('MIN(catalog_prices.delivery_min) as min_days')
            ->pluck('min_days')
            ->map(fn (mixed $days): int => (int) $days)
            ->values()
            ->all();
    }

    /** @return array{min: float, max: float} */
    private function priceRange(Builder $products, int $cityId): array
    {
        $stockIds = DB::table('stocks')
            ->where('stockable_type', $this->morphClass())
            ->where('quantity', '>', 0)
            ->whereIn('stockable_id', $products->select('id'))
            ->pluck('id');

        $range = DB::table('catalog_prices')
            ->where('city_id', $cityId)
            ->whereIn('stock_id', $stockIds)
            ->whereNotNull('price')
            ->selectRaw('MIN(price) as min, MAX(price) as max')
            ->first();

        return TireFacetAssembler::priceRange(
            $range !== null ? (float) $range->min : null,
            $range !== null ? (float) $range->max : null,
        );
    }

    private function morphClass(): string
    {
        return (new TireProduct)->getMorphClass();
    }
}
