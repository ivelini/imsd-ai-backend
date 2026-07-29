<?php

namespace App\Actions\Image;

use App\Models\Image;
use App\Services\Admin\ImageService;
use Illuminate\Database\Eloquent\Collection;

/** Получить список изображений товара. */
final readonly class ListImages
{
    public function __construct(
        private ImageService $imageService,
    ) {}

    public function execute(string $imageableType, int $imageableId): Collection
    {
        $type = $this->imageService->resolveMorphType($imageableType);

        return Image::where('imageable_type', $type)
            ->where('imageable_id', $imageableId)
            ->orderBy('sort')
            ->orderBy('id')
            ->get();
    }
}
