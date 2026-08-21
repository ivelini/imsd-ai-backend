<?php

namespace App\Services\Import;

use App\DTOs\Catalog\OriginInfo;
use App\Models\Catalog\Origin\ProductOrigin;

/**
 * Поиск или создание product_origin по триплету (vendor, manufacture_country, manufacture_year).
 *
 * БД-обвязка вокруг чистого OriginParser (ADR 0001): все три значения null →
 * null (механика не активна), запись не создаётся.
 */
final readonly class OriginResolver
{
    public function resolve(
        ?OriginInfo $vendor,
        ?OriginInfo $manufactureCountry,
        ?OriginInfo $manufactureYear,
    ): ?ProductOrigin {
        if ($vendor === null && $manufactureCountry === null && $manufactureYear === null) {
            return null;
        }

        // Каст set() не применяется к where-условиям firstOrCreate — сериализуем явно
        return ProductOrigin::firstOrCreate([
            'vendor' => $this->encode($vendor),
            'manufacture_country' => $this->encode($manufactureCountry),
            'manufacture_year' => $this->encode($manufactureYear),
        ]);
    }

    private function encode(?OriginInfo $info): ?string
    {
        return $info !== null ? json_encode($info, JSON_UNESCAPED_UNICODE) : null;
    }
}
