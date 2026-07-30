<?php

namespace App\Services\TireImport;

use App\Models\Catalog\Brand;
use App\Models\Catalog\Country;
use App\Models\Catalog\ProductModel;
use App\Models\Catalog\Supplier;
use App\Models\Catalog\Warehouse;
use Illuminate\Support\Str;

/** Поиск или создание справочных сущностей (Brand, Supplier, Country, Warehouse, ProductModel). */
final class ReferenceResolver
{
    public function resolveBrand(string $name): Brand
    {
        $slug = SlugGenerator::fromName($name);

        return Brand::firstOrCreate(
            ['slug' => $slug],
            ['name' => $name, 'slug' => $slug, 'type' => 'tire'],
        );
    }

    public function resolveSupplier(string $name): Supplier
    {
        return Supplier::firstOrCreate(
            ['name' => $name],
            ['name' => $name],
        );
    }

    public function resolveCountry(string $name): ?Country
    {
        $name = trim($name);
        if ($name === '') {
            return null;
        }

        return Country::firstOrCreate(
            ['name' => $name],
            ['name' => $name, 'code' => SlugGenerator::fromName($name, 2)],
        );
    }

    public function resolveWarehouse(string $name): Warehouse
    {
        return Warehouse::firstOrCreate(
            ['name' => $name],
            ['name' => $name],
        );
    }

    /** Поиск или создание модели товара в рамках бренда. */
    public function resolveModel(Brand $brand, string $name, string $type): ProductModel
    {
        $slug = $brand->slug.'-'.Str::slug($name);

        return ProductModel::firstOrCreate(
            ['brand_id' => $brand->id, 'slug' => $slug],
            ['name' => $name, 'type' => $type],
        );
    }

    /** Извлекает название модели из полного названия диска (XLSX). */
    public static function parseWheelModelName(string $name): string
    {
        // Убираем префикс «Диск »
        $stripped = mb_substr($name, 5);

        // Находим первое вхождение размерности: {W}x{D}
        if (preg_match('/\d+[.,]?\d*x\d+/u', $stripped, $m, PREG_OFFSET_CAPTURE)) {
            return trim(mb_substr($stripped, 0, $m[0][1] - 1));
        }

        return trim($stripped);
    }
}
