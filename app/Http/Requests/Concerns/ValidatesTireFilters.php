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
            'brand_id' => ['nullable', 'integer', 'exists:brands,id'],
            'model_id' => ['nullable', 'integer', 'exists:product_models,id'],
            'season' => ['nullable', Rule::in(array_column(Season::cases(), 'value'))],
            'width' => ['nullable', 'array'],
            'width.*' => ['integer', 'min:1', 'max:500'],
            'profile' => ['nullable', 'array'],
            'profile.*' => ['integer', 'min:1', 'max:150'],
            'diameter' => ['nullable', 'array'],
            'diameter.*' => ['string', 'max:10'],
            'load_index' => ['nullable', 'array'],
            'load_index.*' => ['string', 'max:10'],
            'speed_index' => ['nullable', 'array'],
            'speed_index.*' => ['string', 'max:5'],
            'year' => ['nullable', 'array'],
            'year.*' => ['integer', 'min:2000', 'max:2100'],
            'is_published' => ['nullable', 'boolean'],
            'is_studded' => ['nullable', 'boolean'],
            'is_runflat' => ['nullable', 'boolean'],
            'is_xl' => ['nullable', 'boolean'],
            'is_bestseller' => ['nullable', 'boolean'],
            'is_new' => ['nullable', 'boolean'],
            'city_id' => ['nullable', 'integer', 'exists:cities,id'],
        ];
    }

    public function tireFilterBodyParameters(): array
    {
        return [
            'brand_id' => [
                'description' => 'ID бренда для фильтрации.',
                'required' => false,
                'type' => 'integer',
                'example' => 1,
            ],
            'model_id' => [
                'description' => 'ID модели для фильтрации.',
                'required' => false,
                'type' => 'integer',
                'example' => 5,
            ],
            'season' => [
                'description' => 'Сезонность: summer, winter, all-season.',
                'required' => false,
                'type' => 'string',
                'example' => 'winter',
            ],
            'width' => [
                'description' => 'Ширина профиля в мм (массив).',
                'required' => false,
                'type' => 'array',
                'example' => [205, 225],
            ],
            'profile' => [
                'description' => 'Высота профиля в мм (массив).',
                'required' => false,
                'type' => 'array',
                'example' => [55, 65],
            ],
            'diameter' => [
                'description' => 'Посадочный диаметр (массив).',
                'required' => false,
                'type' => 'array',
                'example' => ['R16', 'R17'],
            ],
            'load_index' => [
                'description' => 'Индекс нагрузки (массив).',
                'required' => false,
                'type' => 'array',
                'example' => ['91', '94'],
            ],
            'speed_index' => [
                'description' => 'Индекс скорости (массив).',
                'required' => false,
                'type' => 'array',
                'example' => ['H', 'V'],
            ],
            'year' => [
                'description' => 'Год выпуска (массив).',
                'required' => false,
                'type' => 'array',
                'example' => [2023, 2024],
            ],
            'is_published' => [
                'description' => 'Опубликован на сайте.',
                'required' => false,
                'type' => 'boolean',
                'example' => true,
            ],
            'is_studded' => [
                'description' => 'Шипованная.',
                'required' => false,
                'type' => 'boolean',
                'example' => false,
            ],
            'is_runflat' => [
                'description' => 'Runflat-технология.',
                'required' => false,
                'type' => 'boolean',
                'example' => false,
            ],
            'is_xl' => [
                'description' => 'Усиленная (Extra Load).',
                'required' => false,
                'type' => 'boolean',
                'example' => false,
            ],
            'is_bestseller' => [
                'description' => 'Хит продаж.',
                'required' => false,
                'type' => 'boolean',
                'example' => false,
            ],
            'is_new' => [
                'description' => 'Новинка.',
                'required' => false,
                'type' => 'boolean',
                'example' => true,
            ],
            'city_id' => [
                'description' => 'ID города для расчёта доставки.',
                'required' => false,
                'type' => 'integer',
                'example' => 1,
            ],
        ];
    }
}
