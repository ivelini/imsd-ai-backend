<?php

namespace App\Preconditions\Catalog;

use App\Models\Catalog\Brand;
use DomainException;

/** Проверка: у бренда нет товаров перед удалением. */
final readonly class EnsureBrandHasNoProducts
{
    /** @param  Brand  $brand  с загруженными tire_products_count и wheel_products_count */
    public function ensure(Brand $brand): void
    {
        $productsCount = ($brand->tire_products_count ?? 0) + ($brand->wheel_products_count ?? 0);

        if ($productsCount > 0) {
            throw new DomainException(
                "Невозможно удалить бренд «{$brand->name}»: {$productsCount} товаров использует его."
            );
        }
    }
}
