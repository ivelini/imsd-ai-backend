<?php

namespace App\Http\Resources\Catalog;

use App\Models\Image;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

/** Изображение товара в публичном каталоге. */
final class ImageResource extends JsonResource
{
    /** @var Image */
    public $resource;

    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource->id,
            'url' => Storage::url($this->resource->path),
        ];
    }
}
