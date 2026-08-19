<?php

namespace App\Actions\Import\Wheel;

use App\DTOs\WheelImport\UpsertWheelProductInput;
use App\Enums\Catalog\WheelType;
use App\Models\Catalog\Wheel\WheelProduct;
use App\Services\Catalog\ProductSlugService;
use App\Services\TireImport\ReferenceResolver;

/** Создание или обновление товара (диска) по EAN. */
final readonly class UpsertWheelProduct
{
    /** @var array<string, string> */
    private const WHEEL_TYPE_MAP = [
        'литые' => WheelType::Alloy->value,
        'литой' => WheelType::Alloy->value,
        'штампованные' => WheelType::Steel->value,
        'штампованный' => WheelType::Steel->value,
        'штамповка' => WheelType::Steel->value,
        'кованые' => WheelType::Forged->value,
        'кованый' => WheelType::Forged->value,
        'ковка' => WheelType::Forged->value,
    ];

    public function __construct(
        private ReferenceResolver $referenceResolver,
        private ProductSlugService $slugService,
    ) {}

    public function execute(UpsertWheelProductInput $input): void
    {
        $brand = $this->referenceResolver->resolveBrand($input->brandName, 'wheel');
        $modelName = ReferenceResolver::parseWheelModelName($input->name);
        $model = $this->referenceResolver->resolveModel($brand, $modelName, 'wheel');

        $country = $input->countryName !== null
            ? $this->referenceResolver->resolveCountry($input->countryName)
            : null;

        $pcd = $this->buildPcd($input->pcd1, $input->pcd2);
        $wheelType = $this->resolveWheelType($input->wheelTypeRaw);

        $existing = WheelProduct::where('ean', $input->ean)->first();

        WheelProduct::updateOrCreate(
            ['ean' => $input->ean],
            [
                'brand_id' => $brand->id,
                'model_id' => $model->id,
                'name' => $input->name,
                'slug' => $this->slugService->wheel(
                    brandId: $brand->id,
                    name: $input->name,
                    width: $input->width,
                    diameter: $input->diameter,
                    et: $input->et,
                    pcd: $pcd,
                    hubDiameter: $input->hubDiameter,
                    ignoreId: $existing?->id,
                ),
                'country_id' => $country?->id,
                'type' => $wheelType,
                'color' => $input->color,
                'pcd' => $pcd,
                'hub_diameter' => $input->hubDiameter !== null ? (float) $input->hubDiameter : null,
                'et' => $input->et !== null ? (float) $input->et : null,
                'width' => $input->width !== null ? (float) $input->width : null,
                'diameter' => $input->diameter,
                'description' => $input->description,
            ],
        );
    }

    private function resolveWheelType(?string $raw): string
    {
        if ($raw === null || $raw === '') {
            return 'alloy';
        }

        return self::WHEEL_TYPE_MAP[mb_strtolower(trim($raw))] ?? 'alloy';
    }

    private function buildPcd(?string $pcd1, ?string $pcd2): ?string
    {
        if ($pcd1 === null || $pcd2 === null) {
            return null;
        }

        return trim($pcd1).'*'.trim($pcd2);
    }
}
