<?php

namespace App\Http\Resources\Admin\Catalog\Image;

use App\Models\Image;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

/** Изображение товара во вложенной структуре. */
final class ImageResource extends JsonResource
{
    /** @var Image */
    public $resource;

    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource->id,
            'path' => $this->resource->path,
            'url' => Storage::url($this->resource->path),
            'sort' => $this->resource->sort,
            'is_main' => $this->resource->is_main,
        ];
    }
}
