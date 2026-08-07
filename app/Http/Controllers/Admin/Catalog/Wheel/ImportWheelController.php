<?php

namespace App\Http\Controllers\Admin\Catalog\Wheel;

use App\Actions\Import\StartProductImport;
use App\DTOs\Import\StartImportInput;
use App\Enums\Import\ImportType;
use App\Http\Requests\Admin\Catalog\UploadFileRequest;
use App\Http\Resources\Admin\Catalog\ProductImportResource;
use App\Models\System\ProductImport;
use App\Preconditions\Import\EnsureNoActiveImport;
use Illuminate\Http\JsonResponse;

/** Загрузка и запуск импорта дисков.*/
final readonly class ImportWheelController
{
    public function __construct(
        private StartProductImport $startProductImport,
        private EnsureNoActiveImport $ensureNoActiveImport,
    ) {}

    /**
     * Загрузить XLSX-файл с дисками.
     *
     * @authenticated
     *
     * @bodyParam file file required XLSX-файл (макс. 50 МБ).
     *
     * @response status=202 {"data": {"import_id": 1}}
     *
     * @group Импорт
     */
    public function store(UploadFileRequest $request): JsonResponse
    {
        $this->ensureNoActiveImport->ensure(ImportType::Wheel);

        $importId = $this->startProductImport->execute(new StartImportInput(
            file: $request->file('file'),
            type: ImportType::Wheel,
        ));

        return response()->json(['data' => ['import_id' => $importId]], 202);
    }

    /**
     * Статус импорта дисков.
     *
     * @authenticated
     *
     * @urlParam id int required ID импорта.
     */
    public function show(int $id): ProductImportResource
    {
        return new ProductImportResource(ProductImport::findOrFail($id));
    }
}
