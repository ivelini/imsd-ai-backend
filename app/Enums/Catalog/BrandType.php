<?php

namespace App\Enums\Catalog;

/** Тип бренда: выпускает шины, диски или оба вида товаров. */
enum BrandType: string
{
    case Tire = 'tire';
    case Wheel = 'wheel';
    case Both = 'both';

    public function label(): string
    {
        return match ($this) {
            self::Tire => 'Шинные',
            self::Wheel => 'Дисковые',
            self::Both => 'Шины и диски',
        };
    }

    /** Покрывает ли бренд категорию товаров. */
    public function covers(ProductType $productType): bool
    {
        return match ($this) {
            self::Both => true,
            self::Tire => $productType === ProductType::Tire,
            self::Wheel => $productType === ProductType::Wheel,
        };
    }
}
