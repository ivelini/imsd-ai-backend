<?php

namespace App\DTOs\Import;

use Illuminate\Http\UploadedFile;

/** Входные данные для Action StartProductImport. */
final readonly class StartImportInput
{
    public function __construct(
        public UploadedFile $file,
        public ?string $type,
    ) {}
}
