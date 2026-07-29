<?php

namespace App\Http\Resources\Admin\Catalog\Tire;

use App\Models\Catalog\Brand;
use App\Models\Catalog\Stock;
use App\Models\Catalog\TireProduct;
use App\Models\Catalog\Warehouse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** Ресурс шины. */
final class TireProductResource extends JsonResource
{
    /** @var TireProduct */
    public $resource;

    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        $tire = $this->resource;

        return [
            'id' => $tire->id,
            'brand_id' => $tire->brand_id,
            'brand' => $this->whenLoaded('brand', function () use ($tire) {
                /** @var Brand $brand */
                $brand = $tire->brand;

                return ['id' => $brand->id, 'name' => $brand->name];
            }),
            'name' => $tire->name,
            'supplier_id' => $tire->supplier_id,
            'country_id' => $tire->country_id,
            'ean' => $tire->ean,
            'season' => $tire->season?->value,
            'width' => $tire->width,
            'profile' => $tire->profile,
            'diameter' => $tire->diameter,
            'load_index' => $tire->load_index,
            'speed_index' => $tire->speed_index,
            'is_studded' => $tire->is_studded,
            'is_runflat' => $tire->is_runflat,
            'is_xl' => $tire->is_xl,
            'year' => $tire->year,
            'description' => $tire->description,
            'is_published' => $tire->is_published,
            'is_bestseller' => $tire->is_bestseller,
            'is_new' => $tire->is_new,
            'stocks' => $this->whenLoaded('stocks', function () use ($tire) {
                $result = [];
                foreach ($tire->stocks as $s) {
                    /** @var Stock $s */
                    /** @var Warehouse|null $wh */
                    $wh = $s->warehouse;
                    $result[] = [
                        'warehouse_id' => $s->warehouse_id,
                        'warehouse' => $wh?->name,
                        'quantity' => $s->quantity,
                        'purchase_price' => $s->purchase_price,
                        'price' => $s->price,
                    ];
                }

                return $result;
            }),
            'created_at' => $tire->created_at->toIso8601String(),
        ];
    }
}
