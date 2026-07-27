<?php

namespace App\Http\Resources\Admin\Catalog;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** Ресурс товара для списка каталога (шина/диск). */
final class CatalogProductResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        $p = $this->resource;

        $base = [
            'id' => $p->id,
            'type' => $p->type,
            'brand' => $p->brand_name,
            'name' => $p->name,
            'ean' => $p->ean,
            'is_published' => (bool) $p->is_published,
            'created_at' => $p->created_at,
        ];

        if ($p->type === 'tire') {
            $base['width'] = $p->width;
            $base['profile'] = $p->profile;
            $base['diameter'] = $p->diameter;
            $base['season'] = $p->season;
        } else {
            $base['diameter'] = $p->diameter;
            $base['wheel_width'] = $p->wheel_width;
            $base['pcd'] = $p->pcd;
            $base['et'] = $p->et;
        }

        return $base;
    }
}
