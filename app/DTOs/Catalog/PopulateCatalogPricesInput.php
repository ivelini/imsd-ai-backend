<?php

namespace App\DTOs\Catalog;

/** Входные данные для Action PopulateCatalogPrices. */
final readonly class PopulateCatalogPricesInput
{
    /**
     * @param  int[]|null  $stockIds  null — полный пересчёт всех остатков
     */
    public function __construct(
        public ?array $stockIds = null,
    ) {}
}
