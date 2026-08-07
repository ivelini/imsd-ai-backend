<?php

namespace App\Http\Controllers\Admin\Geo;

use App\Actions\Import\StartProductImport;
use App\DTOs\Import\StartImportInput;
use App\Enums\Import\ImportType;
use App\Http\Requests\Admin\Catalog\UploadFileRequest;
use App\Http\Resources\Admin\Catalog\ProductImportResource;
use App\Models\System\ProductImport;
use App\Preconditions\Import\EnsureNoActiveImport;
use Illuminate\Http\JsonResponse;

/**
 * Загрузка и запуск импорта точек выдачи.
 */
final readonly class ImportPointController
{
    public function __construct(
        private StartProductImport $startProductImport,
        private EnsureNoActiveImport $ensureNoActiveImport,
    ) {}

    /**
     * Загрузить XLSX-файл с точками выдачи.
     *
     * @authenticated
     *
     * @group Импорт
     */
    public function store(UploadFileRequest $request): JsonResponse
    {
        $this->ensureNoActiveImport->ensure(ImportType::Point);

        $importId = $this->startProductImport->execute(new StartImportInput(
            file: $request->file('file'),
            type: ImportType::Point,
        ));

        return response()->json(['data' => ['import_id' => $importId]], 202);
    }

    /**
     * Статус импорта точек выдачи.
     *
     * @authenticated
     *
     * @group Импорт точек выдачи
     */
    public function show(int $id): ProductImportResource
    {
        return new ProductImportResource(ProductImport::findOrFail($id));
    }
}
