<?php

namespace App\Http\Resources\Admin\Catalog\Brand;

use App\Models\Catalog\Brand\Brand;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** Компактный вывод бренда во вложенных структурах (шина, диск). */
final class BrandBriefResource extends JsonResource
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
