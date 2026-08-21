<?php

namespace App\Casts;

use App\DTOs\Catalog\Tire\EuroLabel;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;

/** Каст euro_label: JSON-строка → EuroLabel; мусор в БД не роняет чтение (→ null). */
final readonly class EuroLabelCast implements CastsAttributes
{
    public function get(Model $model, string $key, mixed $value, array $attributes): ?EuroLabel
    {
        if ($value === null || $value === '') {
            return null;
        }

        $decoded = json_decode((string) $value, true);

        return is_array($decoded) ? EuroLabel::fromArray($decoded) : null;
    }

    public function set(Model $model, string $key, mixed $value, array $attributes): ?string
    {
        if ($value === null) {
            return null;
        }

        if ($value instanceof EuroLabel) {
            return json_encode($value, JSON_UNESCAPED_UNICODE);
        }

        return (string) $value;
    }
}
