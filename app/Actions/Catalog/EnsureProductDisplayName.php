<?php

namespace App\Actions\Catalog;

use App\Models\Catalog\ProductModel;

/** Если name не передан — заполняет из model.name. */
final readonly class EnsureProductDisplayName
{
    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function execute(array $data): array
    {
        if (empty($data['name'])) {
            $model = ProductModel::find($data['model_id']);
            $data['name'] = $model->name;
        }

        return $data;
    }
}
