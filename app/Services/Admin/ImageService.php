<?php

namespace App\Services\Admin;

use App\Enums\Catalog\ProductType;
use DomainException;

/** Бизнес-логика работы с изображениями товаров. */
final class ImageService
{
    public const MAX_IMAGES = 10;

    /** @var string[] */
    private array $validTypes;

    public function __construct()
    {
        $this->validTypes = array_column(ProductType::cases(), 'value');
    }

    /** Преобразовать строковый тип в morph-тип (ключ morphMap из AppServiceProvider). */
    public function resolveMorphType(string $type): string
    {
        if (! in_array($type, $this->validTypes, true)) {
            throw new DomainException("Некорректный тип товара: {$type}");
        }

        return $type;
    }

    public function ensureImageLimit(int $currentCount): void
    {
        if ($currentCount >= self::MAX_IMAGES) {
            throw new DomainException(
                'Не более '.self::MAX_IMAGES.' изображений на товар.'
            );
        }
    }

    public function getNextMainImageId(bool $wasMain, array $siblingIds): ?int
    {
        if (! $wasMain || empty($siblingIds)) {
            return null;
        }

        return $siblingIds[0];
    }
}
