<?php

namespace App\Actions\TireImport;

use App\DTOs\TireImport\ImportTireRow;
use App\DTOs\TireImport\UpsertResult;
use App\Models\Catalog\TireProduct;
use App\Services\TireImport\DescriptionBuilder;
use App\Services\TireImport\ReferenceResolver;
use App\Services\TireImport\RowMapper;

/** Создание или обновление товара (шины) по EAN. */
final readonly class UpsertTireProduct
{
    public function __construct(
        private ReferenceResolver $referenceResolver,
        private RowMapper $rowMapper,
        private DescriptionBuilder $descriptionBuilder,
    ) {}

    public function execute(ImportTireRow $row): UpsertResult
    {
        $brand = $this->referenceResolver->resolveBrand($row->brand_name);

        $supplier = $row->supplier_name !== null
            ? $this->referenceResolver->resolveSupplier($row->supplier_name)
            : null;

        $country = $row->country_name !== null
            ? $this->referenceResolver->resolveCountry($row->country_name)
            : null;

        $season = $this->rowMapper->toSeason($row->season_raw);
        $loadIndexResult = $this->rowMapper->parseLoadSpeedIndex($row->load_speed_index);
        $description = json_encode(
            $this->descriptionBuilder->build($row->descriptions),
            JSON_UNESCAPED_UNICODE,
        );

        $exists = TireProduct::where('ean', $row->ean)->exists();

        TireProduct::updateOrCreate(
            ['ean' => $row->ean],
            [
                'brand_id' => $brand->id,
                'name' => $row->name,
                'supplier_id' => $supplier?->id,
                'country_id' => $country?->id,
                'season' => $season,
                'width' => $row->width,
                'profile' => $row->profile,
                'diameter' => $row->diameter,
                'load_index' => $loadIndexResult['load'],
                'speed_index' => $loadIndexResult['speed'],
                'is_studded' => $this->rowMapper->toBool($row->is_studded_raw),
                'is_runflat' => $this->rowMapper->toBool($row->is_runflat_raw),
                'description' => $description,
            ],
        );

        return new UpsertResult(created: ! $exists);
    }
}
