<?php

namespace App\Services\Admin;

use App\Enums\Catalog\ProductType;
use DomainException;

/** Бизнес-логика акций. */
final class PromotionService
{
    /** @var array<string, string> */
    private array $promotableMap;

    public function __construct()
    {
        $this->promotableMap = [
            ProductType::Tire->value => 'tire_product',
            ProductType::Wheel->value => 'wheel_product',
            'brand' => 'brand',
        ];
    }

    public function resolvePromotableType(string $type): string
    {
        return $this->promotableMap[$type]
            ?? throw new DomainException("Некорректный тип привязки: {$type}");
    }
}
