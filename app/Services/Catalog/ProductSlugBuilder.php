<?php

namespace App\Services\Catalog;

use Illuminate\Support\Str;

/**
 * Формула slug товара из характеристик (чистая функция, без БД).
 *
 * Шина: brand-name-width-profile-diameter[-studded][-runflat] (флаги только при true).
 * Диск: brand-name-width-diameter-et-pcd-hub_diameter (pcd «4*98» → «4x98»).
 */
final class ProductSlugBuilder
{
    public static function tire(
        string $brandSlug,
        string $name,
        ?int $width,
        ?int $profile,
        ?string $diameter,
        bool $isStudded,
        bool $isRunflat,
    ): string {
        return implode('-', self::parts([
            $brandSlug,
            Str::slug($name),
            $width,
            $profile,
            $diameter,
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
        return implode('-', self::parts([
            $brandSlug,
            Str::slug($name),
            $width,
            $diameter,
            $et,
            $pcd !== null ? str_replace('*', 'x', $pcd) : null,
            $hubDiameter,
        ]));
    }

    /** @param  list<string|int|null>  $values */
    private static function parts(array $values): array
    {
        return array_values(array_filter($values, fn (string|int|null $value): bool => $value !== null && $value !== ''));
    }
}
