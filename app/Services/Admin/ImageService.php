<?php

namespace App\Services\Admin;

use DomainException;

/** Бизнес-логика работы с изображениями товаров. */
final class ImageService
{
    public const MAX_IMAGES = 10;

    /** Разрешённые типы товаров для полиморфной связи. */
    private const MORPH_MAP = [
        'tire' => 'tire_product',
        'wheel' => 'wheel_product',
    ];

    /**
     * Преобразовать строковый тип товара в morph-класс.
     *
     * @throws DomainException при неизвестном типе.
     */
    public function resolveMorphType(string $type): string
    {
        return self::MORPH_MAP[$type]
            ?? throw new DomainException("Некорректный тип товара: {$type}");
    }

    /**
     * Проверить, не превышен ли лимит изображений.
     *
     * @throws DomainException если достигнут лимит.
     */
    public function ensureImageLimit(int $currentCount): void
    {
        if ($currentCount >= self::MAX_IMAGES) {
            throw new DomainException(
                'Не более '.self::MAX_IMAGES.' изображений на товар.'
            );
        }
    }

    /**
     * Вернуть ID следующего изображения для назначения главным.
     * Если удаляемое было главным — возвращается следующее по sort.
     */
    public function getNextMainImageId(bool $wasMain, array $siblingIds): ?int
    {
        if (! $wasMain || empty($siblingIds)) {
            return null;
        }

        return $siblingIds[0];
    }
}
