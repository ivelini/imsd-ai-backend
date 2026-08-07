<?php

namespace App\Services\Admin;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

/** Загрузка и замена файлов (logo, image) на публичном диске. */
final readonly class FileService
{
    public function store(UploadedFile $file, string $directory, string $disk = 'public'): string
    {
        return $file->store($directory, $disk);
    }

    /** Удалить старый файл (если был) и сохранить новый. */
    public function replace(?string $oldPath, UploadedFile $file, string $directory, string $disk = 'public'): string
    {
        if ($oldPath !== null) {
            Storage::disk($disk)->delete($oldPath);
        }

        return $this->store($file, $directory, $disk);
    }
}
