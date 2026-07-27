<?php

namespace App\Services\TireImport;

/** Генерация slug из имени. */
final class SlugGenerator
{
    public static function fromName(string $name, int $maxLength = 50): string
    {
        $slug = mb_strtolower(trim($name));
        $slug = preg_replace('/[^a-z0-9а-яё\s\-]/u', '', $slug);
        $slug = preg_replace('/[\s\-]+/', '-', $slug);
        $slug = trim($slug, '-');

        return mb_substr($slug, 0, $maxLength);
    }
}
