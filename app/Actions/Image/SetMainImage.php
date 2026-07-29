<?php

namespace App\Actions\Image;

use App\Models\Image;

/** Установить главное изображение. */
final readonly class SetMainImage
{
    public function execute(int $id): void
    {
        $image = Image::findOrFail($id);

        Image::where('imageable_type', $image->imageable_type)
            ->where('imageable_id', $image->imageable_id)
            ->update(['is_main' => false]);

        $image->update(['is_main' => true]);
    }
}
