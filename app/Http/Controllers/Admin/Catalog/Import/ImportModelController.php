<?php

namespace App\Http\Controllers\Admin\Catalog\Import;

use App\Actions\Import\StartProductImport;
use App\DTOs\Import\StartImportInput;
use App\Enums\Import\ImportType;
use App\Http\Requests\Admin\Catalog\UploadFileRequest;
use App\Preconditions\Import\EnsureNoActiveImport;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\JsonResponse;

/** Загрузка и запуск импорта моделей товаров. */
#[Group('Каталог / импорт', weight: 10)]
final readonly class ImportModelController
{
    public function __construct(
        private StartProductImport $startProductImport,
        private EnsureNoActiveImport $ensureNoActiveImport,
    ) {}

    /** Загрузить XLSX-файл с моделями. */
    public function store(UploadFileRequest $request): JsonResponse
    {
        $this->ensureNoActiveImport->ensure(ImportType::Model);

        $importId = $this->startProductImport->execute(new StartImportInput(
            file: $request->file('file'),
            type: ImportType::Model,
        ));

        return response()->json(['data' => ['import_id' => $importId]], 202);
    }
}
