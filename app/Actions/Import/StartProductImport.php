<?php

namespace App\Actions\Import;

use App\DTOs\Import\StartImportInput;
use App\Jobs\CatalogImport\MasterJob;
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

        $importData = [
            'original_filename' => $input->file->getClientOriginalName(),
            'status' => 'pending',
        ];

        if ($input->type !== null) {
            $importData['type'] = $input->type;
        }

        $import = ProductImport::create($importData);

        $fullPath = $this->filesystem->path($path);

        if ($input->type === 'wheel') {
            WheelMasterJob::dispatch(
                $import->id,
                $fullPath,
                config('wheel_import.chunk_size'),
                config('wheel_import.chunk_path'),
                config('wheel_import.required_columns'),
                config('wheel_import.column_map'),
            );
        } elseif ($input->type === 'point') {
            PointImportJob::dispatch(
                $import->id,
                $fullPath,
                config('point_import.column_map'),
                config('point_import.required_columns'),
                config('point_import.boolean_true'),
            );
        } else {
            MasterJob::dispatch(
                $import->id,
                $fullPath,
                config('tire_import.chunk_size'),
                config('tire_import.chunk_path'),
            );
        }

        return $import->id;
    }
}
