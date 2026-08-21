<?php

namespace App\Services\Catalog\Wheel;

use App\Enums\Catalog\DeliveryDaysType;
use App\Enums\Catalog\WheelType;

/**
 * Чистые функции сборки фасетов дисков: порядок и метки значений — единственный источник.
 * Аналог TireFacetAssembler для каталога шин.
 */
final class WheelFacetAssembler
{
    /** @param  list<int|string>  $values  @return list<array{label: int|string, value: int|string}> */
    public static function dimension(array $values): array
    {
        $values = array_values(array_unique($values));
        sort($values, SORT_NUMERIC);

        return array_map(fn (int|string $value): array => ['label' => $value, 'value' => $value], $values);
    }

    /** @param  list<string>  $present  @return list<array{label: string, value: string}> */
    public static function type(array $present): array
    {
        return collect(WheelType::cases())
            ->filter(fn (WheelType $type): bool => in_array($type->value, $present, true))
            ->map(fn (WheelType $type): array => ['label' => $type->label(), 'value' => $type->value])
            ->values()
            ->all();
    }

    /** @param  list<array{name: string, slug: string}>  $items  @return list<array{label: string, value: string}> */
    public static function named(array $items): array
    {
        usort($items, fn (array $a, array $b): int => strcmp($a['name'], $b['name']));

        return array_map(fn (array $item): array => ['label' => $item['name'], 'value' => $item['slug']], $items);
    }

    /** @param  list<int>  $minDays  @return list<array{label: string, value: string}> */
    public static function delivery(array $minDays): array
    {
        $present = [];
        foreach ($minDays as $days) {
            $present[DeliveryDaysType::fromDays($days)->value] = true;
        }

        return collect(DeliveryDaysType::cases())
            ->filter(fn (DeliveryDaysType $type): bool => isset($present[$type->value]))
            ->map(fn (DeliveryDaysType $type): array => ['label' => $type->label(), 'value' => $type->value])
            ->values()
            ->all();
    }

    /** @return array{min: float, max: float} */
    public static function priceRange(?float $min, ?float $max): array
    {
        return ['min' => $min ?? 0.0, 'max' => $max ?? 0.0];
    }
}
