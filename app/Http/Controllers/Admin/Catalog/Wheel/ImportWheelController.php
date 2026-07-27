<?php

namespace App\Http\Controllers\Admin\Catalog\Wheel;

use App\Http\Requests\Admin\Catalog\UploadFileRequest;
use App\Http\Resources\Admin\Catalog\ProductImportResource;
use App\Jobs\CatalogImport\WheelMasterJob;
use App\Models\System\ProductImport;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Http\JsonResponse;

/** Загрузка и запуск импорта дисков.
 *
 * @group Импорт дисков
 */
final readonly class ImportWheelController
{
    public function __construct(
        private Filesystem $filesystem,
    ) {}

    /**
     * Загрузить XLSX-файл с дисками.
     *
     * @authenticated
     *
     * @bodyParam file file required XLSX-файл (макс. 50 МБ).
     *
     * @response status=202 {"data": {"import_id": 1}}
     */
    public function store(UploadFileRequest $request): JsonResponse
    {
        $file = $request->file('file');
        $path = $file->store('import/uploads');

        $import = ProductImport::create([
            'original_filename' => $file->getClientOriginalName(),
            'status' => 'pending',
            'type' => 'wheel',
        ]);

        WheelMasterJob::dispatch(
            $import->id,
            $this->filesystem->path($path),
            config('wheel_import.chunk_size'),
            config('wheel_import.chunk_path'),
            config('wheel_import.required_columns'),
            config('wheel_import.column_map'),
        );

        return response()->json([
            'data' => ['import_id' => $import->id],
        ], 202);
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
