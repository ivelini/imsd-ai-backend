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
}
