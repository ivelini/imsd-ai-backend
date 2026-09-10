<?php

namespace App\Actions\Image;

use App\Models\Image;
use Illuminate\Support\Facades\Storage;

/** Удалить изображение и его файл. Если удалили главное — назначить следующее. */
final readonly class DeleteImage
{
    public function execute(int $id): void
    {
        $image = Image::findOrFail($id);
        $wasMain = $image->is_main;
        $path = $image->path;

        $image->delete();

        Storage::disk('public')->delete($path);

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
