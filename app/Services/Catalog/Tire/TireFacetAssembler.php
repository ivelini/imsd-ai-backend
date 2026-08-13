<?php

namespace App\Services\Catalog\Tire;

use App\Enums\Catalog\DeliveryDaysType;
use App\Enums\Catalog\Season;

/**
 * Сборка фасетов фильтра шин из сырых значений БД (чистые функции, без БД).
 * Единый источник порядка значений и меток фасетов.
 */
final class TireFacetAssembler
{
    /** @param  list<int|string>  $values  @return list<array{label: int|string, value: int|string}> */
    public static function dimension(array $values): array
    {
        $values = array_values(array_unique($values));
        sort($values, SORT_NUMERIC);

        return array_map(fn (int|string $value): array => ['label' => $value, 'value' => $value], $values);
    }

    /** @param  list<int|string>  $values  @return list<array{label: int|string, value: int|string}> */
    public static function diameter(array $values): array
    {
        $values = array_values(array_unique($values));
        sort($values, SORT_NUMERIC);

        return array_map(fn (int|string $value): array => ['label' => $value, 'value' => 'r'.$value], $values);
    }

    /** @param  list<string>  $present  @return list<array{label: string, value: string}> */
    public static function season(array $present): array
    {
        return collect(Season::cases())
            ->filter(fn (Season $season): bool => in_array($season->value, $present, true))
            ->map(fn (Season $season): array => ['label' => $season->label(), 'value' => $season->value])
            ->values()
            ->all();
    }

    /** @param  list<bool>  $values  @return list<array{label: string, value: bool}> */
    public static function studded(array $values): array
    {
        $values = array_values(array_unique($values));
        rsort($values);

        return array_map(
            fn (bool $studded): array => [
                'label' => $studded ? 'Шипованная' : 'Не шипованная',
                'value' => $studded,
            ],
            $values,
        );
    }

    /** @param  list<array{slug: string|null, name: string}>  $items  @return list<array{label: string, value: string|null}> */
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
        return [
            'min' => $min ?? 0.0,
            'max' => $max ?? 0.0,
        ];
    }
}
