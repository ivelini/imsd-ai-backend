<?php

namespace App\Services\Catalog;

use App\Enums\Catalog\BrandType;

/**
 * SEO-заголовок листинга каталога (чистая функция, без БД).
 *
 * title = «{Категория} {brand} {в-фраза}»: категория по типу бренда, город —
 * предложный падеж эвристикой по суффиксам, непокрытые — «в городе {name}».
 */
final class SeoTitleBuilder
{
    public static function category(BrandType $type): string
    {
        return match ($type) {
            BrandType::Tire => 'Шины',
            BrandType::Wheel => 'Диски',
            BrandType::Both => 'Шины и диски',
        };
    }

    /** «в Челябинске» / «в городе Москва» — полная в-фраза для title. */
    public static function prepositionalCity(string $name): string
    {
        // Предложный падеж эвристикой: -ск/-бург/-град получают суффикс -е
        if (str_ends_with($name, 'ск') || str_ends_with($name, 'бург') || str_ends_with($name, 'град')) {
            return 'в '.$name.'е';
        }

        return 'в городе '.$name;
    }

    public static function title(BrandType $type, string $brandName, string $cityName): string
    {
        return self::category($type).' '.$brandName.' '.self::prepositionalCity($cityName);
    }
}
