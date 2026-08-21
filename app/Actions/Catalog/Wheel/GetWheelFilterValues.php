<?php

namespace App\Actions\Catalog\Wheel;

use App\Enums\Catalog\WheelType;
use App\Models\Catalog\Brand\Brand;
use App\Models\Catalog\Country\Country;
use App\Models\Catalog\Wheel\WheelProduct;
use App\Services\Catalog\Wheel\WheelFacetAssembler;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

/**
 * Фасетные значения фильтра дисков: каждый фасет — отдельный SELECT над клоном
 * базового запроса (published + в наличии + активные фильтры).
 */
final readonly class GetWheelFilterValues
{
    public function execute(int $cityId, array $filters): array
    {
        $published = WheelProduct::query()
            ->published(true)
            ->whereHas('stocks', fn (Builder $q) => $q->where('quantity', '>', 0))
            ->byCatalogFilters($cityId, $filters);

        return [
            'width' => WheelFacetAssembler::dimension($this->distinct(clone $published, 'width')),
            'diameter' => WheelFacetAssembler::dimension($this->distinct(clone $published, 'diameter')),
            'pcd' => WheelFacetAssembler::dimension($this->distinct(clone $published, 'pcd')),
            'et' => WheelFacetAssembler::dimension($this->distinct(clone $published, 'et')),
            'hub_diameter' => WheelFacetAssembler::dimension($this->distinct(clone $published, 'hub_diameter')),
            'type' => WheelFacetAssembler::type($this->typeValues(clone $published)),
            'color' => WheelFacetAssembler::dimension($this->distinct(clone $published, 'color')),
            'brand' => WheelFacetAssembler::named($this->brandNames(clone $published)),
            'country' => WheelFacetAssembler::named($this->countryNames(clone $published)),
            'delivery' => WheelFacetAssembler::delivery($this->minDeliveryDays(clone $published, $cityId)),
            'price' => $this->priceRange(clone $published, $cityId),
        ];
    }

    private function distinct(Builder $query, string $column): array
    {
        return $query->whereNotNull($column)->distinct()->pluck($column)->all();
    }

    private function typeValues(Builder $query): array
    {
        // pluck() применяет касты модели — type приходит enum-объектами (WheelTypeCast)
        return $query->whereNotNull('type')->distinct()->pluck('type')
            ->map(fn (WheelType $type): string => $type->value)
            ->all();
    }

    private function brandNames(Builder $query): array
    {
        $ids = $query->whereNotNull('brand_id')->distinct()->pluck('brand_id');

        return Brand::query()->whereIn('id', $ids)
            ->get(['slug', 'name'])
            ->map(fn (Brand $brand): array => ['slug' => $brand->slug, 'name' => $brand->name])
            ->values()
            ->all();
    }

    private function countryNames(Builder $query): array
    {
        $ids = $query->whereNotNull('country_id')->distinct()->pluck('country_id');

        return Country::query()->whereIn('id', $ids)
            ->get(['name', 'slug'])
            ->map(fn (Country $country): array => ['slug' => $country->slug, 'name' => $country->name])
            ->values()
            ->all();
    }

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

        return WheelFacetAssembler::priceRange(
            $range !== null ? (float) $range->min : null,
            $range !== null ? (float) $range->max : null,
        );
    }

    private function morphClass(): string
    {
        return (new WheelProduct)->getMorphClass();
    }
}
