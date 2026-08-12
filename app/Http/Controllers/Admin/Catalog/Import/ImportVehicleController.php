<?php

namespace App\Http\Controllers\Admin\Catalog\Import;

use App\Actions\Import\StartProductImport;
use App\DTOs\Import\StartImportInput;
use App\Enums\Import\ImportType;
use App\Http\Requests\Admin\Vehicle\UploadCsvFileRequest;
use App\Preconditions\Import\EnsureNoActiveImport;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\JsonResponse;

/** Загрузка и запуск импорта характеристик автомобилей. */
#[Group('Каталог / импорт', weight: 10)]
final readonly class ImportVehicleController
{
    public function __construct(
        private StartProductImport $startProductImport,
        private EnsureNoActiveImport $ensureNoActiveImport,
    ) {}

    /** Загрузить CSV-файл с характеристиками автомобилей. */
    public function store(UploadCsvFileRequest $request): JsonResponse
    {
        $this->ensureNoActiveImport->ensure(ImportType::Vehicle);

        $importId = $this->startProductImport->execute(new StartImportInput(
            file: $request->file('file'),
            type: ImportType::Vehicle,
        ));

        return response()->json(['data' => ['import_id' => $importId]], 202);
    }
}
