<?php

namespace App\DTOs\Catalog;

/** Входные данные для Action PopulateCatalogPrices. */
final readonly class PopulateCatalogPricesInput
{
    public function __construct(
        public ?int $importId = null,
    ) {}
}
