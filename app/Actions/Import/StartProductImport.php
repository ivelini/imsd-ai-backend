<?php

namespace App\Actions\Import;

use App\DTOs\Import\ImportMasterJobInput;
use App\DTOs\Import\StartImportInput;
use App\DTOs\VehicleImport\VehicleImportMasterJobInput;
use App\Enums\Import\ImportState;
use App\Enums\Import\ImportType;
use App\Jobs\CatalogImport\ImportMasterJob;
use App\Jobs\CatalogImport\ModelImportJob;
use App\Jobs\GeoImport\PointImportJob;
use App\Jobs\VehicleImport\VehicleImportMasterJob;
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
            'status' => ImportState::Pending->value,
            'type' => $input->type,
        ]);

        $fullPath = $this->filesystem->path($path);

        $importId = $import->id;

        match ($input->type) {
            ImportType::Tire => ImportMasterJob::dispatch(new ImportMasterJobInput(
                importId: $importId,
                filePath: $fullPath,
                chunkSize: config('tire_import.chunk_size'),
                chunkPath: config('tire_import.chunk_path'),
                importType: $input->type,
                requiredColumns: config('tire_import.required_columns', []),
                columnMap: config('tire_import.column_map', []),
            )),
            ImportType::Wheel => ImportMasterJob::dispatch(new ImportMasterJobInput(
                importId: $importId,
                filePath: $fullPath,
                chunkSize: config('wheel_import.chunk_size'),
                chunkPath: config('wheel_import.chunk_path'),
                importType: $input->type,
                requiredColumns: config('wheel_import.required_columns', []),
                columnMap: config('wheel_import.column_map', []),
            )),
            ImportType::Point => PointImportJob::dispatch(
                $importId,
                $fullPath,
                config('point_import.column_map'),
                config('point_import.required_columns'),
                config('point_import.boolean_true'),
            ),
            ImportType::Model => ModelImportJob::dispatch(
                $importId,
                $fullPath,
                config('model_import.column_map'),
                config('model_import.required_columns'),
            ),
            ImportType::Vehicle => VehicleImportMasterJob::dispatch(new VehicleImportMasterJobInput(
                importId: $importId,
                filePath: $fullPath,
                chunkSize: config('vehicle_import.chunk_size'),
                chunkPath: config('vehicle_import.chunk_path'),
            )),
        };

        return $importId;
    }
}
