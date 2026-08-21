<?php

namespace App\Services\Catalog\Tire;

use App\Enums\Catalog\Season;

/**
 * Отображаемое имя шины из характеристик (чистая функция, без БД).
 *
 * «Шина {сезон} {бренд} {модель} {width}/{profile} R{diameter} {load}{speed}» —
 * сезон в нижнем регистре, части пропускаются при отсутствии данных.
 */
final class TireNameBuilder
{
    public static function build(
        Season $season,
        string $brandName,
        string $modelName,
        ?int $width,
        ?int $profile,
        ?string $diameter,
        ?string $loadIndex,
        ?string $speedIndex,
    ): string {
        $index = $loadIndex !== null || $speedIndex !== null
            ? ($loadIndex ?? '').($speedIndex ?? '')
            : null;

        return implode(' ', array_values(array_filter([
            'Шина',
            mb_strtolower($season->label()),
            $brandName,
            $modelName,
            $width !== null && $profile !== null ? $width.'/'.$profile : null,
            $diameter !== null ? 'R'.$diameter : null,
            $index,
        ], fn (?string $part): bool => $part !== null && $part !== '')));
    }
}
