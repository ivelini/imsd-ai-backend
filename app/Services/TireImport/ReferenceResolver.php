<?php

namespace App\Services\TireImport;

use App\Models\Catalog\Brand;
use App\Models\Catalog\Country;
use App\Models\Catalog\Supplier;
use App\Models\Catalog\Warehouse;

/** Поиск или создание справочных сущностей (Brand, Supplier, Country, Warehouse). */
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
}
