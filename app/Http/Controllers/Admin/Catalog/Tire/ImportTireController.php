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

    /**
     * Загрузить XLSX-файл с шинами.
     *
     * @group Импорт
     *
     * @authenticated
     *
     * @bodyParam file file required XLSX-файл (макс. 50 МБ).
     *
     * @response status=202 {"data": {"import_id": 1}}
     * @response status=422 {"message": "The given data was invalid.", "errors": {"file": ["Допустим только формат XLSX."]}}
     */
    public function store(UploadFileRequest $request): JsonResponse
    {
        $this->ensureNoActiveImport->ensure(ImportType::Tire);

        $importId = $this->startProductImport->execute(new StartImportInput(
            file: $request->file('file'),
            type: ImportType::Tire,
        ));

        return response()->json(['data' => ['import_id' => $importId]], 202);
    }

    /**
     * Статус импорта шин.
     *
     * @authenticated
     *
     * @urlParam id int required ID импорта.
     *
     * @responseField id int ID импорта.
     * @responseField status string Статус: pending, processing, completed, failed.
     * @responseField total_rows int Всего строк в файле.
     * @responseField created_rows int Создано товаров.
     * @responseField updated_rows int Обновлено товаров.
     * @responseField failed_rows int Строк с ошибками.
     * @responseField errors array|null Список ошибок.
     */
    public function show(int $id): ProductImportResource
    {
        return new ProductImportResource(ProductImport::findOrFail($id));
    }
}
