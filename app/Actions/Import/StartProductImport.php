<?php

namespace App\Actions\Import;

use App\DTOs\Import\StartImportInput;
use App\Enums\Import\ImportType;
use App\Jobs\CatalogImport\MasterJob;
use App\Jobs\CatalogImport\ModelImportJob;
use App\Jobs\CatalogImport\WheelMasterJob;
use App\Jobs\GeoImport\PointImportJob;
use App\Models\System\ProductImport;
use Illuminate\Contracts\Filesystem\Filesystem;

/** Сохранить файл импорта, создать запись ProductImport, запустить Job. */
final readonly class StartProductImport
{
    public function __construct(
        private Filesystem $filesystem,
    ) {}

    /** @return int ID созданного ProductImport */
    public function execute(StartImportInput $input): int
    {
        $path = $input->file->store('import/uploads');

        $import = ProductImport::create([
            'original_filename' => $input->file->getClientOriginalName(),
            'status' => 'pending',
            'type' => $input->type,
        ]);

        $fullPath = $this->filesystem->path($path);

        $importId = $import->id;

        match ($input->type) {
            ImportType::Wheel => WheelMasterJob::dispatch(
                $importId,
                $fullPath,
                config('wheel_import.chunk_size'),
                config('wheel_import.chunk_path'),
                config('wheel_import.required_columns'),
                config('wheel_import.column_map'),
            ),
            ImportType::Point => PointImportJob::dispatch(
                $importId,
                $fullPath,
                config('point_import.column_map'),
                config('point_import.required_columns'),
                config('point_import.boolean_true'),
            ),
            ImportType::Tire => MasterJob::dispatch(
                $importId,
                $fullPath,
                config('tire_import.chunk_size'),
                config('tire_import.chunk_path'),
            ),
            ImportType::Model => ModelImportJob::dispatch(
                $importId,
                $fullPath,
                config('model_import.column_map'),
                config('model_import.required_columns'),
            ),
        };

        return $importId;
    }
}
