<?php

namespace App\Http\Controllers\Admin\Catalog\Tire;

use App\Http\Requests\Admin\Catalog\UploadFileRequest;
use App\Http\Resources\Admin\Catalog\ProductImportResource;
use App\Jobs\CatalogImport\MasterJob;
use App\Models\System\ProductImport;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Http\JsonResponse;

/** Загрузка и запуск импорта шин.
 *
 * @group Импорт шин
 */
final readonly class ImportTireController
{
    public function __construct(
        private Filesystem $filesystem,
    ) {}

    /**
     * Загрузить XLSX-файл с шинами.
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
        $file = $request->file('file');
        $path = $file->store('import/uploads');

        $import = ProductImport::create([
            'original_filename' => $file->getClientOriginalName(),
            'status' => 'pending',
        ]);

        MasterJob::dispatch($import->id, $this->filesystem->path($path));

        return response()->json([
            'data' => [
                'import_id' => $import->id,
            ],
        ], 202);
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
        $import = ProductImport::findOrFail($id);

        return new ProductImportResource($import);
    }
}
