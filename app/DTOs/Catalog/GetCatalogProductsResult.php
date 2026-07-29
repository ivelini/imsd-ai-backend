<?php

namespace App\DTOs\Catalog;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/** Результат Action GetCatalogProducts — обёртка над пагинатором. */
final readonly class GetCatalogProductsResult
{
    public function __construct(
        public LengthAwarePaginator $paginator,
    ) {}
}
