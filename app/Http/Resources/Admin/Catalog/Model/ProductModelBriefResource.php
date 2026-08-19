<?php

namespace App\Http\Resources\Admin\Catalog\Model;

use App\Models\Catalog\Model\ProductModel;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** Компактный вывод модели товара во вложенных структурах (шина, диск). */
final class ProductModelBriefResource extends JsonResource
{
    /** @var ProductModel */
    public $resource;

    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource->id,
            'name' => $this->resource->name,
            'slug' => $this->resource->slug,
            'image' => $this->resource->image,
        ];
    }
}
