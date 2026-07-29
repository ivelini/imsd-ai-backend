<?php

namespace App\Http\Controllers\Admin\Geo;

use App\Actions\Import\StartProductImport;
use App\DTOs\Import\StartImportInput;
use App\Http\Requests\Admin\Catalog\UploadFileRequest;
use App\Http\Resources\Admin\Catalog\ProductImportResource;
use App\Models\System\ProductImport;
use Illuminate\Http\JsonResponse;

/** Загрузка и запуск импорта точек выдачи. */
final readonly class ImportPointController
{
    public function __construct(
        private StartProductImport $startProductImport,
    ) {}

    /**
     * Загрузить XLSX-файл с точками выдачи.
     *
     * @authenticated
     *
     * @group Импорт точек выдачи
     */
    public function store(UploadFileRequest $request): JsonResponse
    {
        $importId = $this->startProductImport->execute(new StartImportInput(
            file: $request->file('file'),
            type: 'point',
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
