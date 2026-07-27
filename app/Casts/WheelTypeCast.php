<?php

namespace App\Casts;

use App\Enums\Catalog\WheelType;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;

/** Каст WheelType-поля модели: string → WheelType enum. */
final readonly class WheelTypeCast implements CastsAttributes
{
    public function get(Model $model, string $key, mixed $value, array $attributes): ?WheelType
    {
        if ($value === null) {
            return null;
        }

        return WheelType::from($value);
    }

    public function set(Model $model, string $key, mixed $value, array $attributes): ?string
    {
        if ($value === null) {
            return null;
        }

        if ($value instanceof WheelType) {
            return $value->value;
        }

        return $value;
    }
}
