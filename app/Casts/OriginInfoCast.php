<?php

namespace App\Casts;

use App\DTOs\Catalog\OriginInfo;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;

/** Каст колонки product_origin: JSON → OriginInfo; мусор в БД не роняет чтение (→ null). */
final readonly class OriginInfoCast implements CastsAttributes
{
    public function get(Model $model, string $key, mixed $value, array $attributes): ?OriginInfo
    {
        if ($value === null || $value === '') {
            return null;
        }

        $decoded = json_decode((string) $value, true);

        return is_array($decoded) ? OriginInfo::fromArray($decoded) : null;
    }

    public function set(Model $model, string $key, mixed $value, array $attributes): ?string
    {
        if ($value === null) {
            return null;
        }

        if ($value instanceof OriginInfo) {
            return json_encode($value, JSON_UNESCAPED_UNICODE);
        }

        return (string) $value;
    }
}
