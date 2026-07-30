<?php

namespace App\Http\Requests\Concerns;

use App\Enums\Catalog\WheelType;
use Illuminate\Validation\Rule;

/** Общие правила валидации фильтров дисков. Используется админкой и публичным API. */
trait ValidatesWheelFilters
{
    public function wheelFilterRules(): array
    {
        return [
            'brand_id' => ['nullable', 'integer', 'exists:brands,id'],
            'model_id' => ['nullable', 'integer', 'exists:product_models,id'],
            'type' => ['nullable', Rule::in(array_column(WheelType::cases(), 'value'))],
            'color' => ['nullable', 'string', 'max:50'],
            'width' => ['nullable', 'array'],
            'width.*' => ['numeric', 'min:1', 'max:500'],
            'diameter' => ['nullable', 'array'],
            'diameter.*' => ['integer', 'min:1', 'max:50'],
            'pcd' => ['nullable', 'array'],
            'pcd.*' => ['string', 'max:20'],
            'et' => ['nullable', 'array'],
            'et.*' => ['numeric'],
            'hub_diameter' => ['nullable', 'array'],
            'hub_diameter.*' => ['numeric'],
            'is_published' => ['nullable', 'boolean'],
            'is_bestseller' => ['nullable', 'boolean'],
            'is_new' => ['nullable', 'boolean'],
            'city_id' => ['nullable', 'integer', 'exists:cities,id'],
        ];
    }

    public function wheelFilterBodyParameters(): array
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
            'type' => [
                'description' => 'Тип диска: alloy, steel, forged.',
                'required' => false,
                'type' => 'string',
                'example' => 'alloy',
            ],
            'color' => [
                'description' => 'Цвет диска.',
                'required' => false,
                'type' => 'string',
                'example' => 'Чёрный',
            ],
            'width' => [
                'description' => 'Ширина диска в дюймах (массив).',
                'required' => false,
                'type' => 'array',
                'example' => [6.5, 7.0],
            ],
            'diameter' => [
                'description' => 'Посадочный диаметр в дюймах (массив).',
                'required' => false,
                'type' => 'array',
                'example' => [16, 17],
            ],
            'pcd' => [
                'description' => 'PCD-сверловка (массив).',
                'required' => false,
                'type' => 'array',
                'example' => ['5x112', '5x114.3'],
            ],
            'et' => [
                'description' => 'Вылет ET в мм (массив).',
                'required' => false,
                'type' => 'array',
                'example' => [35, 45],
            ],
            'hub_diameter' => [
                'description' => 'Диаметр ступицы в мм (массив).',
                'required' => false,
                'type' => 'array',
                'example' => [57.1, 66.6],
            ],
            'is_published' => [
                'description' => 'Опубликован на сайте.',
                'required' => false,
                'type' => 'boolean',
                'example' => true,
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
