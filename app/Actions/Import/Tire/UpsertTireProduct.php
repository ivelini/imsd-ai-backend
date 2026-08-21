<?php

namespace App\Actions\Import\Tire;

use App\DTOs\TireImport\ImportTireRow;
use App\DTOs\TireImport\UpsertResult;
use App\Enums\Catalog\Season;
use App\Models\Catalog\Tire\TireProduct;
use App\Services\Catalog\ProductSlugService;
use App\Services\Catalog\Tire\TireNameBuilder;
use App\Services\Import\OriginResolver;
use App\Services\TireImport\ReferenceResolver;
use App\Services\TireImport\RowMapper;

/** Создание или обновление товара (шины) по EAN. */
final readonly class UpsertTireProduct
{
    public function __construct(
        private ReferenceResolver $referenceResolver,
        private RowMapper $rowMapper,
        private OriginResolver $originResolver,
        private ProductSlugService $slugService,
    ) {}

    public function execute(ImportTireRow $row): UpsertResult
    {
        $brand = $this->referenceResolver->resolveBrand($row->brand_name);
        $model = $this->referenceResolver->resolveModel($brand, $row->name, 'tire');

        if ($row->description_present) {
            $model->update(['description' => $row->description]);
        }

        $origin = $this->originResolver->resolve(
            $row->origin_vendor,
            $row->origin_manufacture_country,
            $row->origin_manufacture_year,
        );

        $country = $row->country_name !== null
            ? $this->referenceResolver->resolveCountry($row->country_name)
            : null;

        $season = $this->rowMapper->toSeason($row->season_raw);
        $loadIndexResult = $this->rowMapper->parseLoadSpeedIndex($row->load_speed_index);

        $isStudded = $this->rowMapper->toBool($row->is_studded_raw);
        $isRunflat = $this->rowMapper->toBool($row->is_runflat_raw);
        $name = TireNameBuilder::build(
            season: Season::from($season),
            brandName: $brand->name,
            modelName: $model->name,
            width: $row->width,
            profile: $row->profile,
            diameter: $row->diameter,
            loadIndex: $loadIndexResult['load'],
            speedIndex: $loadIndexResult['speed'],
        );

        $existing = TireProduct::where('ean', $row->ean)->first();

        $data = [
            'brand_id' => $brand->id,
            'model_id' => $model->id,
            'name' => $name,
            'slug' => $this->slugService->tire(
                brandId: $brand->id,
                modelId: $model->id,
                width: $row->width,
                profile: $row->profile,
                diameter: $row->diameter,
                loadIndex: $loadIndexResult['load'],
                speedIndex: $loadIndexResult['speed'],
                isStudded: $isStudded,
                isRunflat: $isRunflat,
                ignoreId: $existing?->id,
            ),
            'country_id' => $country?->id,
            'season' => $season,
            'width' => $row->width,
            'profile' => $row->profile,
            'diameter' => $row->diameter,
            'load_index' => $loadIndexResult['load'],
            'speed_index' => $loadIndexResult['speed'],
            'is_studded' => $isStudded,
            'is_runflat' => $isRunflat,
            'euro_label' => $row->euroLabel,
        ];

        if ($row->origin_present) {
            $data['origin_id'] = $origin?->id;
        }

        TireProduct::updateOrCreate(['ean' => $row->ean], $data);

        return new UpsertResult(created: $existing === null);
    }
}
