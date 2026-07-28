<?php

namespace App\Http\Requests\Admin\Catalog;

use App\Enums\Catalog\Season;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/** Валидация создания и обновления шины. */
class TireProductRequest extends FormRequest
{
    public function rules(): array
    {
        $tireId = $this->route('id');

        return [
            'brand_id' => ['required', 'integer', 'exists:brands,id'],
            'name' => ['required', 'string', 'max:255'],
            'supplier_id' => ['nullable', 'integer', 'exists:suppliers,id'],
            'country_id' => ['nullable', 'integer', 'exists:countries,id'],
            'ean' => [
                'nullable', 'string', 'max:50',
                Rule::unique('tire_products', 'ean')->ignore($tireId),
            ],
            'season' => ['required', Rule::in(array_column(Season::cases(), 'value'))],
            'width' => ['nullable', 'integer', 'min:100', 'max:400'],
            'profile' => ['nullable', 'integer', 'min:20', 'max:100'],
            'diameter' => ['nullable', 'string', 'max:10'],
            'load_index' => ['nullable', 'string', 'max:10'],
            'speed_index' => ['nullable', 'string', 'max:5'],
            'is_studded' => ['boolean'],
            'is_runflat' => ['boolean'],
            'is_xl' => ['boolean'],
            'year' => ['nullable', 'integer', 'min:2000', 'max:2030'],
            'description' => ['nullable', 'string'],
            'is_published' => ['boolean'],
            'is_bestseller' => ['boolean'],
            'is_new' => ['boolean'],
        ];
    }

    public function bodyParameters(): array
    {
        return [
            'brand_id' => [
                'description' => 'ID бренда.',
                'required' => true,
                'type' => 'integer',
                'example' => 1,
            ],
            'name' => [
                'description' => 'Название модели шины.',
                'required' => true,
                'type' => 'string',
                'example' => 'Primacy 4',
            ],
            'supplier_id' => [
                'description' => 'ID поставщика.',
                'required' => false,
                'type' => 'integer',
            ],
            'country_id' => [
                'description' => 'ID страны производителя.',
                'required' => false,
                'type' => 'integer',
            ],
            'ean' => [
                'description' => 'EAN-код товара.',
                'required' => false,
                'type' => 'string',
                'example' => '8714691012345',
            ],
            'season' => [
                'description' => 'Сезонность: summer, winter или all-season.',
                'required' => true,
                'type' => 'string',
                'example' => 'summer',
            ],
            'width' => [
                'description' => 'Ширина профиля, мм.',
                'required' => false,
                'type' => 'integer',
                'example' => 225,
            ],
            'profile' => [
                'description' => 'Высота профиля, мм.',
                'required' => false,
                'type' => 'integer',
                'example' => 55,
            ],
            'diameter' => [
                'description' => 'Посадочный диаметр, дюймы.',
                'required' => false,
                'type' => 'string',
                'example' => 'R17',
            ],
            'load_index' => [
                'description' => 'Индекс нагрузки.',
                'required' => false,
                'type' => 'string',
                'example' => '91',
            ],
            'speed_index' => [
                'description' => 'Индекс скорости.',
                'required' => false,
                'type' => 'string',
                'example' => 'V',
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
            'year' => [
                'description' => 'Год выпуска.',
                'required' => false,
                'type' => 'integer',
                'example' => 2025,
            ],
            'description' => [
                'description' => 'Описание товара.',
                'required' => false,
                'type' => 'string',
            ],
            'is_published' => [
                'description' => 'Опубликован.',
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
                'example' => false,
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'brand_id.required' => 'Бренд обязателен.',
            'name.required' => 'Название модели обязательно.',
            'season.required' => 'Сезонность обязательна.',
            'ean.unique' => 'Товар с таким EAN уже существует.',
        ];
    }
}
