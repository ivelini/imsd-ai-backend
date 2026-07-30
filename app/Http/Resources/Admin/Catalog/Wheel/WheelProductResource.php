<?php

namespace App\Http\Resources\Admin\Catalog\Wheel;

use App\Models\Catalog\Brand;
use App\Models\Catalog\ProductModel;
use App\Models\Catalog\Stock;
use App\Models\Catalog\Warehouse;
use App\Models\Catalog\WheelProduct;
use App\Models\Image;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

/** Ресурс диска — только форматирование, без логики. */
final class WheelProductResource extends JsonResource
{
    /** @var WheelProduct */
    public $resource;

    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        $wheel = $this->resource;

        return [
            'id' => $wheel->id,
            'brand_id' => $wheel->brand_id,
            'brand' => $this->whenLoaded('brand', function () use ($wheel) {
                /** @var Brand $brand */
                $brand = $wheel->brand;

                return ['id' => $brand->id, 'name' => $brand->name];
            }),
            'model' => $this->whenLoaded('model', function () use ($wheel) {
                /** @var ProductModel $model */
                $model = $wheel->model;

                return [
                    'id' => $model->id,
                    'name' => $model->name,
                    'slug' => $model->slug,
                    'image' => $model->image,
                ];
            }),
            'name' => $wheel->name,
            'supplier_id' => $wheel->supplier_id,
            'country_id' => $wheel->country_id,
            'ean' => $wheel->ean,
            'type' => $wheel->type?->value,
            'color' => $wheel->color,
            'pcd' => $wheel->pcd,
            'et' => $wheel->et,
            'hub_diameter' => $wheel->hub_diameter,
            'width' => $wheel->width,
            'diameter' => $wheel->diameter,
            'description' => $wheel->description,
            'is_published' => $wheel->is_published,
            'is_bestseller' => $wheel->is_bestseller,
            'is_new' => $wheel->is_new,
            'stocks' => $this->whenLoaded('stocks', function () use ($wheel) {
                $result = [];
                foreach ($wheel->stocks as $s) {
                    /** @var Stock $s */
                    /** @var Warehouse|null $wh */
                    $wh = $s->warehouse;
                    $result[] = [
                        'warehouse_id' => $s->warehouse_id,
                        'warehouse' => $wh?->name,
                        'quantity' => $s->quantity,
                        'purchase_price' => $s->purchase_price,
                        'price' => $s->price,
                        'delivery_days' => $s->deliveryDays,
                    ];
                }

                return $result;
            }),
            'delivery' => $this->whenLoaded('delivery'),
            'images' => $this->whenLoaded('images', function () use ($wheel) {
                return $wheel->images->map(function ($img) {
                    /** @var Image $img */
                    return [
                        'id' => $img->id,
                        'path' => $img->path,
                        'url' => Storage::url($img->path),
                        'sort' => $img->sort,
                        'is_main' => $img->is_main,
                    ];
                })->values()->all();
            }),
            'created_at' => $wheel->created_at->toIso8601String(),
        ];
    }
}
