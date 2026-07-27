<?php

namespace App\Casts;

use App\Enums\Catalog\Season;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;

/** Каст Season-поля модели: string → Season enum. */
final readonly class SeasonCast implements CastsAttributes
{
    public function get(Model $model, string $key, mixed $value, array $attributes): ?Season
    {
        if ($value === null) {
            return null;
        }

        return Season::from($value);
    }

    public function set(Model $model, string $key, mixed $value, array $attributes): ?string
    {
        if ($value === null) {
            return null;
        }

        if ($value instanceof Season) {
            return $value->value;
        }

        return $value;
    }
}
