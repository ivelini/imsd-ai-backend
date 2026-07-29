<?php

namespace App\Actions\Image;

use App\Models\Image;

/** Обновить порядок изображений. */
final readonly class ReorderImages
{
    public function execute(array $ids): void
    {
        foreach ($ids as $index => $imageId) {
            Image::where('id', $imageId)->update(['sort' => $index]);
        }
    }
}
