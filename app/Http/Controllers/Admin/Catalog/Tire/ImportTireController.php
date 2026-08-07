<?php

namespace App\Http\Controllers\Admin\Catalog\Tire;

use App\Actions\Import\StartProductImport;
use App\DTOs\Import\StartImportInput;
use App\Enums\Import\ImportType;
use App\Http\Requests\Admin\Catalog\UploadFileRequest;
use App\Http\Resources\Admin\Catalog\ProductImportResource;
use App\Models\System\ProductImport;
use App\Preconditions\Import\EnsureNoActiveImport;
use Illuminate\Http\JsonResponse;

/** Загрузка и запуск импорта шин.
 *
 */
final readonly class ImportTireController
{
    public function __construct(
        private StartProductImport $startProductImport,
        private EnsureNoActiveImport $ensureNoActiveImport,
    ) {}

    /** Загрузить XLSX-файл с шинами. */
    public function store(UploadFileRequest $request): JsonResponse
    {
        $this->ensureNoActiveImport->ensure(ImportType::Tire);

        $importId = $this->startProductImport->execute(new StartImportInput(
            file: $request->file('file'),
            type: ImportType::Tire,
        ));

        return response()->json(['data' => ['import_id' => $importId]], 202);
    }

    /** Статус импорта шин. */
    public function show(int $id): ProductImportResource
    {
        return new ProductImportResource(ProductImport::findOrFail($id));
    }
}
