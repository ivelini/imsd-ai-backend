<?php

namespace App\Services\Catalog;

use Illuminate\Support\Str;

/**
 * Формула slug товара из характеристик (чистая функция, без БД).
 *
 * Шина: brand-model-width-profile-r{diameter}-{load}{speed}[-studded][-runflat] —
 * индексы и флаги только при наличии/true, всё в lowercase.
 * Диск: brand-name-width-diameter-et-pcd-hub (pcd «4*98» → «4x98»), точки → дефисы.
 */
final class ProductSlugBuilder
{
    public static function tire(
        string $brandSlug,
        string $modelSlug,
        ?int $width,
        ?int $profile,
        ?string $diameter,
        ?string $loadIndex,
        ?string $speedIndex,
        bool $isStudded,
        bool $isRunflat,
    ): string {
        $index = strtolower(($loadIndex ?? '').($speedIndex ?? ''));

        return implode('-', self::parts([
            $brandSlug,
            $modelSlug,
            $width,
            $profile,
            $diameter !== null ? 'r'.$diameter : null,
            $index !== '' ? $index : null,
            $isStudded ? 'studded' : null,
            $isRunflat ? 'runflat' : null,
        ]));
    }

    public static function wheel(
        string $brandSlug,
        string $name,
        ?string $width,
        ?int $diameter,
        ?string $et,
        ?string $pcd,
        ?string $hubDiameter,
    ): string {
        $slug = implode('-', self::parts([
            $brandSlug,
            Str::slug($name),
            $width,
            $diameter,
            $et,
            $pcd !== null ? str_replace('*', 'x', $pcd) : null,
            $hubDiameter,
        ]));

        return str_replace('.', '-', $slug);
    }

    /** @param  list<string|int|null>  $values */
    private static function parts(array $values): array
    {
        return array_values(array_filter($values, fn (string|int|null $value): bool => $value !== null && $value !== ''));
    }
}
