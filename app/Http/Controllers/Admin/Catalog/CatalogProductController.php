<?php

namespace App\Http\Controllers\Admin\Catalog;

use App\Http\Resources\Admin\Catalog\CatalogProductResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;

/** Список товаров каталога (шины + диски) с фильтрацией. */
final readonly class CatalogProductController
{
    private const PER_PAGE = 50;

    /**
     * Список товаров.
     *
     * @queryParam type string Фильтр по типу: tire, wheel.
     * @queryParam brand_id int Фильтр по ID бренда.
     * @queryParam is_published bool Фильтр по статусу публикации.
     * @queryParam search string Поиск по названию, артикулу, EAN.
     * @queryParam sort_by string Поле сортировки: name, brand_name, created_at, ean.
     * @queryParam sort_dir string Направление: asc, desc.
     *
     * @group Каталог
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $type = $request->query('type');
        $brandId = $request->query('brand_id');
        $isPublished = $request->query('is_published');
        $search = $request->query('search');
        $sortBy = $request->query('sort_by', 'created_at');
        $sortDir = $request->query('sort_dir', 'desc');
        $perPage = (int) $request->query('per_page', self::PER_PAGE);

        $perPage = min(max($perPage, 10), 100);

        $tires = DB::table('tire_products')
            ->select([
                'tire_products.id',
                DB::raw("'tire' as type"),
                'brands.name as brand_name',
                'tire_products.name',
                'tire_products.ean',
                'tire_products.is_published',
                'tire_products.width',
                DB::raw('tire_products.profile'),
                'tire_products.diameter',
                'tire_products.season',
                DB::raw('null as wheel_width'),
                DB::raw('null as pcd'),
                DB::raw('null as et'),
                'tire_products.created_at',
            ])
            ->join('brands', 'tire_products.brand_id', '=', 'brands.id');

        $wheels = DB::table('wheel_products')
            ->select([
                'wheel_products.id',
                DB::raw("'wheel' as type"),
                'brands.name as brand_name',
                'wheel_products.name',
                'wheel_products.ean',
                'wheel_products.is_published',
                DB::raw('null as width'),
                DB::raw('null as profile'),
                'wheel_products.diameter',
                DB::raw('null as season'),
                'wheel_products.width as wheel_width',
                'wheel_products.pcd',
                'wheel_products.et',
                'wheel_products.created_at',
            ])
            ->join('brands', 'wheel_products.brand_id', '=', 'brands.id');

        // Apply filters
        if ($type === 'tire') {
            $wheels->whereRaw('0 = 1');
        } elseif ($type === 'wheel') {
            $tires->whereRaw('0 = 1');
        }

        if ($brandId !== null) {
            $tires->where('tire_products.brand_id', (int) $brandId);
            $wheels->where('wheel_products.brand_id', (int) $brandId);
        }

        if ($isPublished !== null) {
            $val = filter_var($isPublished, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
            if ($val !== null) {
                $tires->where('tire_products.is_published', $val);
                $wheels->where('wheel_products.is_published', $val);
            }
        }

        if ($search !== null && $search !== '') {
            $q = '%'.$search.'%';
            $tires->where(function ($query) use ($q) {
                $query->where('tire_products.name', 'like', $q)
                    ->orWhere('tire_products.ean', 'like', $q);
            });
            $wheels->where(function ($query) use ($q) {
                $query->where('wheel_products.name', 'like', $q)
                    ->orWhere('wheel_products.ean', 'like', $q);
            });
        }

        // Union and paginate
        $union = $tires->unionAll($wheels);

        $allowedSort = ['name', 'brand_name', 'created_at', 'ean'];
        $sortBy = in_array($sortBy, $allowedSort) ? $sortBy : 'created_at';
        $sortDir = $sortDir === 'asc' ? 'asc' : 'desc';

        $products = DB::query()
            ->fromSub($union, 'products')
            ->orderBy($sortBy, $sortDir)
            ->paginate($perPage);

        return CatalogProductResource::collection($products);
    }
}
