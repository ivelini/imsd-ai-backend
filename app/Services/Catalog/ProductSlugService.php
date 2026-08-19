<?php

namespace App\Services\Catalog;

use Illuminate\Support\Facades\DB;

/**
 * Slug товара с обеспечением уникальности.
 *
 * БД-обвязка вокруг чистого ProductSlugBuilder (ADR 0001): коллизии
 * (одинаковые характеристики, разные товары) получают суффикс -2, -3.
 */
final readonly class ProductSlugService
{
    public function tire(
        int $brandId,
        string $name,
        ?int $width,
        ?int $profile,
        ?string $diameter,
        bool $isStudded,
        bool $isRunflat,
        ?int $ignoreId = null,
    ): string {
        $base = ProductSlugBuilder::tire(
            $this->brandSlug($brandId),
            $name,
            $width,
            $profile,
            $diameter,
            $isStudded,
            $isRunflat,
        );

        return $this->unique($base, 'tire_products', $ignoreId);
    }

    public function wheel(
        int $brandId,
        string $name,
        ?string $width,
        ?int $diameter,
        ?string $et,
        ?string $pcd,
        ?string $hubDiameter,
        ?int $ignoreId = null,
    ): string {
        $base = ProductSlugBuilder::wheel(
            $this->brandSlug($brandId),
            $name,
            $width,
            $diameter,
            $et,
            $pcd,
            $hubDiameter,
        );

        return $this->unique($base, 'wheel_products', $ignoreId);
    }

    private function brandSlug(int $brandId): string
    {
        return (string) DB::table('brands')->where('id', $brandId)->value('slug');
    }

    private function unique(string $base, string $table, ?int $ignoreId): string
    {
        $slug = $base;
        $i = 2;

        while (DB::table($table)->where('slug', $slug)
            ->when($ignoreId !== null, fn ($q) => $q->where('id', '!=', $ignoreId))
            ->exists()
        ) {
            $slug = $base.'-'.$i++;
        }

        return $slug;
    }
}
