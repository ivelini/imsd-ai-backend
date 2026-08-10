<?php

namespace App\Http\Requests\Concerns;

use App\Enums\Catalog\Season;
use Illuminate\Validation\Rule;

/** Общие правила валидации фильтров шин. Используется админкой и публичным API. */
trait ValidatesTireFilters
{
    public function tireFilterRules(): array
    {
        return [
            /** ID бренда для фильтрации. */
            'brand_id' => ['nullable', 'integer', 'exists:brands,id'],
            /** ID модели для фильтрации. */
            'model_id' => ['nullable', 'integer', 'exists:product_models,id'],
            /** Сезонность: summer, winter, all-season. */
            'season' => ['nullable', Rule::in(array_column(Season::cases(), 'value'))],
            /** Ширина профиля в мм (массив). */
            'width' => ['nullable', 'array'],
            'width.*' => ['integer', 'min:1', 'max:500'],
            /** Высота профиля в % (массив). */
            'profile' => ['nullable', 'array'],
            'profile.*' => ['integer', 'min:1', 'max:150'],
            /** Посадочный диаметр в дюймах (массив). */
            'diameter' => ['nullable', 'array'],
            'diameter.*' => ['string', 'max:10'],
            /** Индекс нагрузки (массив). */
            'load_index' => ['nullable', 'array'],
            'load_index.*' => ['string', 'max:10'],
            /** Индекс скорости (массив). */
            'speed_index' => ['nullable', 'array'],
            'speed_index.*' => ['string', 'max:5'],
            /** Год выпуска (массив). */
            'year' => ['nullable', 'array'],
            'year.*' => ['integer', 'min:2000', 'max:2100'],
            /** Опубликован на сайте. */
            'is_published' => ['nullable', 'boolean'],
            /** Шипованная. */
            'is_studded' => ['nullable', 'boolean'],
            /** Runflat-технология. */
            'is_runflat' => ['nullable', 'boolean'],
            /** Усиленная (Extra Load). */
            'is_xl' => ['nullable', 'boolean'],
            /** Хит продаж. */
            'is_bestseller' => ['nullable', 'boolean'],
            /** Новинка. */
            'is_new' => ['nullable', 'boolean'],
            /** ID города для расчёта доставки. */
            'city_id' => ['nullable', 'integer', 'exists:cities,id'],
        ];
    }
}
