<?php

namespace App\Services\TireImport;

/** Сборка JSONB-поля description из 5 типов описаний. */
final class DescriptionBuilder
{
    /**
     * @param  array<string, string|null>  $descriptions
     * @return array<string, string>
     */
    public function build(array $descriptions): array
    {
        return array_filter($descriptions, fn ($v) => $v !== null && $v !== '');
    }
}
