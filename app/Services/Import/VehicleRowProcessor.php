<?php

namespace App\Services\Import;

use App\DTOs\VehicleImport\ParsedTireSize;
use App\DTOs\VehicleImport\ParsedWheelSpec;
use App\Models\Vehicle\VehicleMake;
use App\Models\Vehicle\VehicleModel;
use App\Models\Vehicle\VehicleModification;
use App\Models\Vehicle\VehicleTireSize;
use App\Models\Vehicle\VehicleWheelSpec;
use App\Services\VehicleImport\VehicleRowParser;
use RuntimeException;

/** Обработка одной строки CSV-чанка: upsert иерархии Vehicle. */
final readonly class VehicleRowProcessor implements ChunkRowProcessor
{
    public function __construct(
        private VehicleRowParser $parser,
    ) {}

    public function process(array $rowData): bool
    {
        /** @var array<int, string> $cols */
        $cols = $rowData;

        $makeName = $cols[0];
        $modelName = $cols[1];
        $generation = $cols[2] ?? '';
        $modName = $cols[3];
        $year = ($cols[4] ?? '') !== '' ? (int) $cols[4] : null;

        $this->guardNotEmpty($makeName, 'make');
        $this->guardNotEmpty($modelName, 'model');
        $this->guardNotEmpty($modName, 'modification');

        $make = VehicleMake::firstOrCreate(['name' => $makeName]);

        $model = VehicleModel::firstOrCreate([
            'make_id' => $make->id,
            'name' => $modelName,
            'generation' => $generation,
        ]);

        $modification = VehicleModification::firstOrCreate([
            'model_id' => $model->id,
            'name' => $modName,
            'year' => $year,
        ]);

        $created = false;

        $tireColumns = [
            5 => 'stock',
            6 => 'optional',
            7 => 'optional',
        ];

        foreach ($tireColumns as $col => $type) {
            $cell = $cols[$col] ?? '';
            foreach ($this->parser->parseTireSizes($cell, $type) as $size) {
                $tire = $this->upsertTireSize($modification->id, $size);
                if ($tire->wasRecentlyCreated) {
                    $created = true;
                }
            }
        }

        $pcd = $cols[11] ?? '';
        $hubDia = $cols[12] ?? '';
        $bolts = $cols[13] ?? '';

        $wheelColumns = [
            8 => 'stock',
            9 => 'optional',
            10 => 'optional',
        ];

        foreach ($wheelColumns as $col => $type) {
            $cell = $cols[$col] ?? '';
            foreach ($this->parser->parseWheelSpecs($cell, $type, $pcd, $hubDia, $bolts) as $spec) {
                $wheel = $this->upsertWheelSpec($modification->id, $spec);
                if ($wheel->wasRecentlyCreated) {
                    $created = true;
                }
            }
        }

        return $created;
    }

    private function upsertTireSize(int $modificationId, ParsedTireSize $size): VehicleTireSize
    {
        return VehicleTireSize::firstOrCreate([
            'modification_id' => $modificationId,
            'type' => $size->type,
            'position' => $size->position?->value,
            'width' => $size->width,
            'profile' => $size->profile,
            'diameter' => $size->diameter,
        ]);
    }

    private function upsertWheelSpec(int $modificationId, ParsedWheelSpec $spec): VehicleWheelSpec
    {
        return VehicleWheelSpec::firstOrCreate([
            'modification_id' => $modificationId,
            'type' => $spec->type,
            'position' => $spec->position?->value,
            'width' => $spec->width,
            'diameter' => $spec->diameter,
            'et' => $spec->et,
            'pcd' => $spec->pcd,
            'hub_diameter' => $spec->hubDiameter,
            'bolts' => $spec->bolts,
        ]);
    }

    private function guardNotEmpty(string $value, string $field): void
    {
        if ($value === '') {
            throw new RuntimeException("Поле {$field} не может быть пустым.");
        }
    }
}
