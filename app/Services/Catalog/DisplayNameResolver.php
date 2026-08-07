<?php

namespace App\Services\Catalog;

use App\Models\Catalog\Model\ProductModel;

/** Если name не передан — заполняет из model.name. */
final readonly class DisplayNameResolver
{
    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function resolve(array $data): array
    {
        if (empty($data['name'])) {
            $data['name'] = ProductModel::findOrFail($data['model_id'])->name;
        }

        return $data;
    }
}
