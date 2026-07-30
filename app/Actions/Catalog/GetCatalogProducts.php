<?php

namespace App\Actions\Catalog;

use App\DTOs\Catalog\GetCatalogProductsInput;
use App\DTOs\Catalog\GetCatalogProductsResult;
use App\Enums\Catalog\ProductType;
use App\Models\Catalog\Builders\TireProductBuilder;
use App\Models\Catalog\Builders\WheelProductBuilder;
use App\Models\Catalog\Tire\TireProduct;
use App\Models\Catalog\Wheel\WheelProduct;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

/** Строит union-запрос товаров каталога (шины + диски) с фильтрацией и пагинацией. */
final readonly class GetCatalogProducts
{
    public function execute(GetCatalogProductsInput $input): GetCatalogProductsResult
    {
        $tireQuery = $this->tireQuery();
        $wheelQuery = $this->wheelQuery();

        $this->applyFilters($tireQuery, $wheelQuery, $input);
        $union = $this->buildUnion($input->type, $tireQuery, $wheelQuery);

        $paginator = DB::query()
            ->fromSub($union, 'products')
            ->orderBy($input->sortBy, $input->sortDir)
            ->paginate($input->perPage);

        return new GetCatalogProductsResult($paginator);
    }

    /** Стандартный select для шин. */
    private function tireQuery(): TireProductBuilder
    {
        /** @var TireProductBuilder */
        return TireProduct::query()
            ->select([
                'tire_products.id',
                DB::raw("'".ProductType::Tire->value."' as type"),
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
    }

    /** Стандартный select для дисков. */
    private function wheelQuery(): WheelProductBuilder
    {
        /** @var WheelProductBuilder */
        return WheelProduct::query()
            ->select([
                'wheel_products.id',
                DB::raw("'".ProductType::Wheel->value."' as type"),
                'brands.name as brand_name',
                'wheel_products.name',
                'wheel_products.ean',
                'wheel_products.is_published',
                DB::raw('null as width'),
                DB::raw('null as profile'),
                DB::raw('CAST(wheel_products.diameter AS TEXT) as diameter'),
                DB::raw('null as season'),
                'wheel_products.width as wheel_width',
                'wheel_products.pcd',
                'wheel_products.et',
                'wheel_products.created_at',
            ])
            ->join('brands', 'wheel_products.brand_id', '=', 'brands.id');
    }

    /** Применяет общие фильтры через Builder-методы. */
    private function applyFilters(TireProductBuilder $tireQuery, WheelProductBuilder $wheelQuery, GetCatalogProductsInput $input): void
    {
        if ($input->search !== null && $input->search !== '') {
            $tireQuery->search($input->search);
            $wheelQuery->search($input->search);
        }

        if ($input->brandId !== null) {
            $tireQuery->byBrand($input->brandId);
            $wheelQuery->byBrand($input->brandId);
        }

        if ($input->isPublished !== null) {
            $tireQuery->published($input->isPublished);
            $wheelQuery->published($input->isPublished);
        }
    }

    /** Строит union в зависимости от фильтра по типу. */
    private function buildUnion(?string $type, TireProductBuilder $tireQuery, WheelProductBuilder $wheelQuery): Builder
    {
        if ($type === ProductType::Tire->value) {
            return $tireQuery;
        }

        if ($type === ProductType::Wheel->value) {
            return $wheelQuery;
        }

        return $tireQuery->unionAll($wheelQuery->getQuery());
    }
}
