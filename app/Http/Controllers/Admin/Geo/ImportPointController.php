<?php

namespace App\Http\Controllers\Admin\Geo;

use App\Http\Requests\Admin\Catalog\UploadFileRequest;
use App\Http\Resources\Admin\Catalog\ProductImportResource;
use App\Jobs\GeoImport\PointImportJob;
use App\Models\System\ProductImport;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Http\JsonResponse;

/** Загрузка и запуск импорта точек выдачи. */
final readonly class ImportPointController
{
    public function __construct(
        private Filesystem $filesystem,
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
        $file = $request->file('file');
        $path = $file->store('import/uploads');

        $import = ProductImport::create([
            'original_filename' => $file->getClientOriginalName(),
            'status' => 'pending',
            'type' => 'point',
        ]);

        PointImportJob::dispatch(
            $import->id,
            $this->filesystem->path($path),
            config('point_import.column_map'),
            config('point_import.required_columns'),
            config('point_import.boolean_true'),
        );

        return response()->json([
            'data' => ['import_id' => $import->id],
        ], 202);
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
