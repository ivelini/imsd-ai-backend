<?php

namespace App\Preconditions\Catalog;

use App\Models\Catalog\Model\ProductModel;
use DomainException;

/** Проверка: у модели нет товаров перед удалением. */
final readonly class EnsureModelHasNoProducts
{
    /** @param  ProductModel  $model  с загруженными tire_products_count и wheel_products_count */
    public function ensure(ProductModel $model): void
    {
        $count = ($model->tire_products_count ?? 0) + ($model->wheel_products_count ?? 0);

        if ($count > 0) {
            throw new DomainException(
                "Невозможно удалить модель «{$model->name}»: {$count} товаров использует её."
            );
        }
    }
}
