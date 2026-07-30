<?php

namespace App\Preconditions\Catalog;

use App\Models\Catalog\Supplier\Supplier;
use DomainException;

/** Проверка: у поставщика нет товаров перед удалением. */
final readonly class EnsureSupplierHasNoProducts
{
    /** @param  Supplier  $supplier  с загруженными tire_products_count и wheel_products_count */
    public function ensure(Supplier $supplier): void
    {
        $productsCount = ($supplier->tire_products_count ?? 0) + ($supplier->wheel_products_count ?? 0);

        if ($productsCount > 0) {
            throw new DomainException(
                "Невозможно удалить поставщика «{$supplier->name}»: {$productsCount} товаров ссылается на него."
            );
        }
    }
}
