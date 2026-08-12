<?php

namespace App\Services\Import;

/** Обнаружение динамических колонок в заголовках XLSX. */
final class ColumnDetector
{
    /**
     * Колонки диапазонов цен: «0-5000», «5001-8500».
     *
     * @param  string[]  $headers
     * @return string[]
     */
    public static function detectPriceColumns(array $headers): array
    {
        return array_values(array_filter($headers, static fn (string $h): bool => (bool) preg_match('/^\d+-\d+$/u', $h)));
    }

    /**
     * Колонки весовых коэффициентов доставки: «31-60 кг», «61-100кг».
     *
     * @param  string[]  $headers
     * @return string[]
     */
    public static function detectDeliveryColumns(array $headers): array
    {
        return array_values(array_filter($headers, static fn (string $h): bool => (bool) preg_match('/^\d+-\d+\s*кг$/ui', $h)));
    }
}
