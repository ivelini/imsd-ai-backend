<?php

namespace App\Casts;

use App\ValueObjects\Money;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;

/**
 * Каст денежных полей (копейки в БД): чтение — Money, запись — Money,
 * int или числовая строка (фабрики и сидеры).
 */
final readonly class MoneyCast implements CastsAttributes
{
    public function get(Model $model, string $key, mixed $value, array $attributes): ?Money
    {
        return $value === null ? null : Money::fromKopecks((int) $value);
    }

    public function set(Model $model, string $key, mixed $value, array $attributes): ?int
    {
        if ($value === null) {
            return null;
        }

        if ($value instanceof Money) {
            return $value->toKopecks();
        }

        if (is_int($value) || (is_string($value) && is_numeric($value))) {
            return (int) $value;
        }

        throw new InvalidArgumentException('Денежное поле принимает Money, int или числовую строку');
    }
}
