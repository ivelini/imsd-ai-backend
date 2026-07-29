<?php

namespace App\Actions\Image;

use App\Models\Image;

/** Удалить изображение. Если удалили главное — назначить следующее. */
final readonly class DeleteImage
{
    public function execute(int $id): void
    {
        $image = Image::findOrFail($id);
        $wasMain = $image->is_main;

        $image->delete();

        if ($wasMain) {
            $next = Image::where('imageable_type', $image->imageable_type)
                ->where('imageable_id', $image->imageable_id)
                ->orderBy('sort')
                ->first();

            if ($next) {
                $next->update(['is_main' => true]);
            }
        }
    }
}
