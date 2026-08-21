<?php

namespace App\Actions\Import\Tire;

use App\DTOs\TireImport\ImportTireRow;
use App\DTOs\TireImport\UpsertResult;
use App\Models\Catalog\Tire\TireProduct;
use App\Services\Catalog\ProductSlugService;
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
        private ProductSlugService $slugService,
    ) {}

    public function execute(ImportTireRow $row): UpsertResult
    {
        $brand = $this->referenceResolver->resolveBrand($row->brand_name);
        $model = $this->referenceResolver->resolveModel($brand, $row->name, 'tire');

        $country = $row->country_name !== null
            ? $this->referenceResolver->resolveCountry($row->country_name)
            : null;

        $season = $this->rowMapper->toSeason($row->season_raw);
        $loadIndexResult = $this->rowMapper->parseLoadSpeedIndex($row->load_speed_index);
        $descriptions = $this->descriptionBuilder->build($row->descriptions);
        // Пустые описания — null, а не "[]": JSON-массив не соответствует формату объекта
        $description = $descriptions === [] ? null : json_encode($descriptions, JSON_UNESCAPED_UNICODE);

        $isStudded = $this->rowMapper->toBool($row->is_studded_raw);
        $isRunflat = $this->rowMapper->toBool($row->is_runflat_raw);

        $existing = TireProduct::where('ean', $row->ean)->first();

        TireProduct::updateOrCreate(
            ['ean' => $row->ean],
            [
                'brand_id' => $brand->id,
                'model_id' => $model->id,
                'name' => $row->name,
                'slug' => $this->slugService->tire(
                    width: $row->width,
                    profile: $row->profile,
                    diameter: $row->diameter,
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
                'description' => $description,
            ],
        );

        return new UpsertResult(created: $existing === null);
    }
}
