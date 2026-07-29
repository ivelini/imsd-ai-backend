<?php

namespace App\DTOs\Image;

use Illuminate\Http\UploadedFile;

/** Входные данные для Action UploadImage. */
final readonly class UploadImageInput
{
    public function __construct(
        public string $imageableType,
        public int $imageableId,
        /** @var UploadedFile */
        public mixed $file,
    ) {}
}
