<?php

namespace App\Http\Resources\Admin\Catalog\Model;

use App\Models\Catalog\Brand\Brand;
use App\Models\Catalog\Model\ProductModel;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** Ресурс модели товара. */
final class ProductModelResource extends JsonResource
{
    /** @var ProductModel */
    public $resource;

    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        /** @var ProductModel $model */
        $model = $this->resource;

        return [
            'id' => $model->id,
            'brand_id' => $model->brand_id,
            'brand' => $this->whenLoaded('brand', function () use ($model) {
                /** @var Brand $brand */
                $brand = $model->brand;

                return ['id' => $brand->id, 'name' => $brand->name];
            }),
            'name' => $model->name,
            'slug' => $model->slug,
            'description' => $model->description,
            'image' => $model->image,
            'type' => $model->type,
            'products_count' => $this->whenCounted('tireProducts',
                fn () => ($model->tire_products_count ?? 0)
                    + ($model->wheel_products_count ?? 0),
            ),
            'created_at' => $model->created_at->toIso8601String(),
        ];
    }
}
