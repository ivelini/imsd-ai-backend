<?php

namespace App\Http\Resources\Catalog;

use App\Models\Catalog\Brand\Brand;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** Бренд в публичном каталоге. */
final class BrandResource extends JsonResource
{
    /** @var Brand */
    public $resource;

    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource->id,
            'name' => $this->resource->name,
        ];
    }
}
