<?php

namespace App\Services\TireImport;

use App\Enums\Catalog\BrandType;
use App\Models\Catalog\Brand\Brand;
use App\Models\Catalog\Country\Country;
use App\Models\Catalog\Model\ProductModel;
use App\Models\Catalog\Warehouse\Warehouse;
use Illuminate\Support\Str;

/** Поиск или создание справочных сущностей (Brand, Country, Warehouse, ProductModel). */
final class ReferenceResolver
{
    public function resolveBrand(string $name, string $productType = 'tire'): Brand
    {
        $slug = Str::slug($name);

        $brand = Brand::firstOrCreate(
            ['slug' => $slug],
            ['name' => $name, 'slug' => $slug, 'type' => $productType],
        );

        // При импорте другого типа продукта — повышаем бренд до «both»
        if ($brand->type->value !== $productType && $brand->type !== BrandType::Both) {
            $brand->update(['type' => BrandType::Both->value]);
        }

        return $brand;
    }

    public function resolveCountry(string $name): ?Country
    {
        $name = trim($name);
        if ($name === '') {
            return null;
        }

        $country = Country::firstOrCreate(
            ['name' => $name],
            ['name' => $name, 'slug' => Str::slug($name)],
        );

        return $country;
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
