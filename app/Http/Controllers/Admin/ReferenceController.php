<?php

namespace App\Http\Controllers\Admin;

use App\Enums\Catalog\ProductType;
use App\Enums\Catalog\Season;
use App\Enums\Catalog\WheelType;
use App\Enums\Promotion\PromotionType;
use App\Models\Catalog\Brand;
use App\Models\Catalog\Country;
use App\Models\Catalog\Supplier;
use Illuminate\Http\JsonResponse;

/** Все справочники и enum-значения для дропдаунов. */
final readonly class ReferenceController
{
    /**
     * Все справочники одним запросом.
     *
     * @group Справочники
     */
    public function index(): JsonResponse
    {
        return response()->json([
            'data' => [
                // Динамические справочники (из БД)
                'brands' => Brand::orderBy('name')
                    ->get(['id', 'name'])
                    ->map(fn (Brand $brand) => ['value' => $brand->id, 'label' => $brand->name]),

                'suppliers' => Supplier::orderBy('name')
                    ->get(['id', 'name'])
                    ->map(fn (Supplier $supplier) => ['value' => $supplier->id, 'label' => $supplier->name]),

                'countries' => Country::orderBy('name')
                    ->get(['id', 'name'])
                    ->map(fn (Country $country) => ['value' => $country->id, 'label' => $country->name]),

                // Фиксированные enum-значения (из кода)
                'tire_seasons' => array_map(fn (Season $case) => [
                    'value' => $case->value,
                    'label' => $case->label(),
                ], Season::cases()),

                'wheel_types' => array_map(fn (WheelType $case) => [
                    'value' => $case->value,
                    'label' => $case->label(),
                ], WheelType::cases()),

                'product_types' => array_map(fn (ProductType $case) => [
                    'value' => $case->value,
                    'label' => $case->label(),
                ], ProductType::cases()),

                'promotion_types' => array_map(fn (PromotionType $case) => [
                    'value' => $case->value,
                    'label' => $case->label(),
                ], PromotionType::cases()),

                'brand_types' => [
                    ['value' => 'tire', 'label' => 'Шинные'],
                    ['value' => 'wheel', 'label' => 'Дисковые'],
                    ['value' => 'both', 'label' => 'Шины и диски'],
                ],
            ],
        ]);
    }
}
