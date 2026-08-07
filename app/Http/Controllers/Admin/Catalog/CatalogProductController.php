<?php

namespace App\Http\Controllers\Admin\Catalog;

use App\Actions\Catalog\GetCatalogProducts;
use App\DTOs\Catalog\GetCatalogProductsInput;
use App\Http\Requests\Admin\Catalog\CatalogProductIndexRequest;
use App\Http\Resources\Admin\Catalog\CatalogProductResource;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/** Список товаров каталога (шины + диски) с фильтрацией. */
final readonly class CatalogProductController
{
    public function __construct(
        private GetCatalogProducts $getCatalogProducts,
    ) {}

    public function index(CatalogProductIndexRequest $request): AnonymousResourceCollection
    {
        $result = $this->getCatalogProducts->execute(
            GetCatalogProductsInput::fromRequest($request)
        );

        return CatalogProductResource::collection($result->paginator);
    }
}
