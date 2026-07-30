<?php

namespace App\DTOs\Import;

use App\Enums\Import\ImportType;
use Illuminate\Http\UploadedFile;

/** Входные данные для Action StartProductImport. */
final readonly class StartImportInput
{
    public function __construct(
        public UploadedFile $file,
        public ImportType $type,
    ) {}
}
