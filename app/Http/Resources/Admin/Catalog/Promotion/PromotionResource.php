<?php

namespace App\Http\Resources\Admin\Catalog\Promotion;

use App\Models\Catalog\Promotion\Promotion;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Ресурс акции.
 *
 * @mixin Promotion
 */
final class PromotionResource extends JsonResource
{
    /** @var Promotion */
    public $resource;

    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource->id,
            'name' => $this->resource->name,
            'description' => $this->resource->description,
            'type' => $this->resource->type,
            'value' => $this->resource->value,
            'starts_at' => $this->resource->starts_at->toIso8601String(),
            'ends_at' => $this->resource->ends_at->toIso8601String(),
            'promotable_type' => $this->resource->promotable_type,
            'promotable_id' => $this->resource->promotable_id,
            'created_at' => $this->resource->created_at->toIso8601String(),
        ];
    }
}
