<?php

namespace App\Enums\Catalog;

/** Бакет срока доставки для фасета каталога. */
enum DeliveryDaysType: string
{
    case ToDay = 'today';
    case Between1and3days = 'between1and3days';
    case Between3and5days = 'between3and5days';
    case After5days = 'after5days';

    /** Бакет по минимальному сроку доставки: 0, 1–3, 4–5, 6+. */
    public static function fromDays(int $days): self
    {
        if ($days === 0) {
            return self::ToDay;
        }

        if ($days <= 3) {
            return self::Between1and3days;
        }

        if ($days <= 5) {
            return self::Between3and5days;
        }

        return self::After5days;
    }

    public function label(): string
    {
        return match ($this) {
            self::ToDay => 'Сегодня',
            self::Between1and3days => 'От 1 до 3 дней',
            self::Between3and5days => 'От 3 до 5 дней',
            self::After5days => 'После 5 дней',
        };
    }

    /** Нижняя граница бакета в днях. */
    public function minDays(): int
    {
        return match ($this) {
            self::ToDay => 0,
            self::Between1and3days => 1,
            self::Between3and5days => 4,
            self::After5days => 6,
        };
    }

    /** Верхняя граница бакета в днях; null — без верхней границы. */
    public function maxDays(): ?int
    {
        return match ($this) {
            self::ToDay => 0,
            self::Between1and3days => 3,
            self::Between3and5days => 5,
            self::After5days => null,
        };
    }
}
