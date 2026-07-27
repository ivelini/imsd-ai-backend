<?php

namespace App\Http\Resources\Admin\Catalog;

use App\Models\Catalog\Warehouse;
use App\Models\Catalog\WarehouseMarkupRule;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** Ресурс правила наценки. */
final class MarkupRuleResource extends JsonResource
{
    /** @var WarehouseMarkupRule */
    public $resource;

    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        $rule = $this->resource;

        return [
            'id' => $rule->id,
            'warehouse_id' => $rule->warehouse_id,
            'warehouse' => $this->whenLoaded('warehouse', function () use ($rule) {
                /** @var Warehouse $wh */
                $wh = $rule->warehouse;

                return ['id' => $wh->id, 'name' => $wh->name];
            }),
            'price_from' => $rule->price_from,
            'price_to' => $rule->price_to,
            'coefficient' => $rule->coefficient,
            'created_at' => $rule->created_at->toIso8601String(),
        ];
    }
}
