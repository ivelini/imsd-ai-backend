<?php

namespace App\Http\Resources\Catalog;

use App\Models\Catalog\Model\ProductModel;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** Модель товара в публичном листинге: {id, name, slug}. */
final class ProductModelReferenceResource extends JsonResource
{
    /** @var ProductModel */
    public $resource;

    /** @return array{id: int, name: string, slug: string} */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource->id,
            'name' => $this->resource->name,
            'slug' => $this->resource->slug,
        ];
    }
}
