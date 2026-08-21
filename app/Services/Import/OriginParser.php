<?php

namespace App\Services\Import;

use App\DTOs\Catalog\OriginInfo;

/**
 * Парсинг значения колонок origin_* (чистая функция, без БД).
 *
 * «##Badge## <p>описание</p>» → OriginInfo(badge, description с HTML);
 * мусор (нет «##…##») или пусто → null — в БД не попадает.
 */
final class OriginParser
{
    public static function parse(?string $value): ?OriginInfo
    {
        $value = trim($value ?? '');
        if ($value === '') {
            return null;
        }

        if (! preg_match('/^##(.+?)##(.*)$/su', $value, $m)) {
            return null;
        }

        $badge = trim($m[1]);
        $description = trim($m[2]);

        if ($badge === '') {
            return null;
        }

        return new OriginInfo(
            badge: $badge,
            description: $description !== '' ? $description : null,
        );
    }
}
