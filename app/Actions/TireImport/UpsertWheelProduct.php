<?php

namespace App\Actions\TireImport;

use App\Enums\Catalog\WheelType;
use App\Models\Catalog\WheelProduct;
use App\Services\TireImport\ReferenceResolver;
use DomainException;

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
    ) {}

    public function execute(
        string $ean,
        string $brandName,
        string $name,
        ?string $countryName,
        ?string $color,
        ?int $diameter,
        ?string $width,
        ?string $pcd1,
        ?string $pcd2,
        ?string $hubDiameter,
        ?string $et,
        ?string $wheelTypeRaw,
        ?string $supplierName,
        ?string $description,
    ): void {
        if ($ean === '') {
            throw new DomainException('EAN не может быть пустым.');
        }

        $brand = $this->referenceResolver->resolveBrand($brandName);
        $supplier = $supplierName !== null
            ? $this->referenceResolver->resolveSupplier($supplierName)
            : null;
        $country = $countryName !== null
            ? $this->referenceResolver->resolveCountry($countryName)
            : null;

        $pcd = $this->buildPcd($pcd1, $pcd2);
        $wheelType = $this->resolveWheelType($wheelTypeRaw);

        WheelProduct::updateOrCreate(
            ['ean' => $ean],
            [
                'brand_id' => $brand->id,
                'name' => $name,
                'supplier_id' => $supplier?->id,
                'country_id' => $country?->id,
                'type' => $wheelType,
                'color' => $color,
                'pcd' => $pcd,
                'hub_diameter' => $hubDiameter !== null ? (float) $hubDiameter : null,
                'et' => $et !== null ? (float) $et : null,
                'width' => $width !== null ? (float) $width : null,
                'diameter' => $diameter,
                'description' => $description,
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
