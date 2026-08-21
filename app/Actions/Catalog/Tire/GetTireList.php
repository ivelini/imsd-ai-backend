<?php

namespace App\Actions\Catalog\Tire;

use App\DTOs\Catalog\Tire\TireListInput;
use App\Models\Catalog\Builders\TireProductBuilder;
use App\Models\Catalog\Tire\TireProduct;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

/**
 * Пагинированный список опубликованных шин с ценой выбранного города.
 *
 * БД-обвязка: фильтры через скоупы TireProductBuilder, цены города — одним
 * батч-запросом по стокам страницы (паттерн GetWarehouseStock).
 */
final readonly class GetTireList
{
    public function execute(TireListInput $input): LengthAwarePaginator
    {
        $perPage = min(max($input->perPage, 10), 100);

        $query = TireProduct::query()
            ->published(true)
            ->whereHas('stocks', fn (Builder $q) => $q->where('quantity', '>', 0))
            ->with([
                'brand',
                'model',
                'origin',
                'images' => fn ($q) => $q->orderBy('is_main', 'desc')->orderBy('sort')->orderBy('id'),
            ])
            // requireCityPrice: товар без цены города в листинг не попадает
            ->byCatalogFilters($input->cityId, $input->filters, requireCityPrice: true);

        $this->applySort($query, $input);

        $paginator = $query->paginate($perPage, ['tire_products.*'], 'page', $input->page);

        $this->enrichCityPrices($paginator, $input->cityId);

        return $paginator;
    }

    private function applySort(TireProductBuilder $query, TireListInput $input): void
    {
        if ($input->sortBy === 'price') {
            $dir = strtoupper($input->sortDir);

            // Скалярный подзапрос — не ломает count-запрос пагинации.
            // CAST: у MIN() нет affinity, иначе sqlite сравнивает некорректно.
            $query->orderByRaw(
                '(SELECT CAST(MIN(cp.price) AS NUMERIC) FROM catalog_prices cp '
                .'JOIN stocks s ON s.id = cp.stock_id '
                .'WHERE s.stockable_id = tire_products.id AND s.stockable_type = ? AND s.quantity > 0 '
                .'AND cp.city_id = ? AND cp.price IS NOT NULL) '.$dir,
                [$this->morphClass(), $input->cityId],
            );
        }

        // tiebreaker — стабильный порядок страниц
        $query->orderBy('tire_products.id', 'desc');
    }

    /** Цены/сроки города для страницы одним запросом → transient-атрибуты моделей. */
    private function enrichCityPrices(LengthAwarePaginator $paginator, int $cityId): void
    {
        $ids = collect($paginator->items())->pluck('id');

        if ($ids->isEmpty()) {
            return;
        }

        $rows = DB::table('stocks')
            ->join('catalog_prices', 'catalog_prices.stock_id', '=', 'stocks.id')
            ->where('stocks.stockable_type', $this->morphClass())
            ->whereIn('stocks.stockable_id', $ids)
            ->where('stocks.quantity', '>', 0)
            ->where('catalog_prices.city_id', $cityId)
            ->whereNotNull('catalog_prices.price')
            ->groupBy('stocks.stockable_id')
            ->selectRaw(
                'stocks.stockable_id as id, MIN(catalog_prices.price) as price, '
                .'MIN(catalog_prices.delivery_min) as delivery_min, MAX(catalog_prices.delivery_max) as delivery_max'
            )
            ->get()
            ->keyBy('id');

        foreach ($paginator->items() as $tire) {
            $row = $rows->get($tire->id);
            $tire->setAttribute('city_price', $row !== null ? (float) $row->price : null);
            $tire->setAttribute('city_delivery_min', $row?->delivery_min);
            $tire->setAttribute('city_delivery_max', $row?->delivery_max);
        }
    }

    private function morphClass(): string
    {
        return (new TireProduct)->getMorphClass();
    }
}
