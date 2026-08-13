<?php

namespace App\Actions\Catalog\Tire;

use App\Enums\Catalog\Season;
use App\Models\Catalog\Brand\Brand;
use App\Models\Catalog\Country\Country;
use App\Models\Catalog\Tire\TireProduct;
use App\Services\Catalog\Tire\TireFacetAssembler;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

/**
 * Фасетные значения фильтра каталога шин для города по умолчанию.
 *
 * БД-обвязка: читает сырые значения, сборку фасетов делегирует TireFacetAssembler.
 */
final readonly class GetTireFilterValues
{
    public function execute(int $cityId): array
    {
        $published = TireProduct::query()
            ->published(true)
            ->whereHas('stocks', fn (Builder $q) => $q->where('quantity', '>', 0));

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
