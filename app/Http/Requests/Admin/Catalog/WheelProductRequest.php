<?php

namespace App\Http\Requests\Admin\Catalog;

use App\Enums\Catalog\WheelType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/** Валидация диска. */
class WheelProductRequest extends FormRequest
{
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
                'description' => 'Название модели диска.',
                'required' => true,
                'type' => 'string',
                'example' => 'SL 521',
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
                'example' => '8714691098765',
            ],
            'type' => [
                'description' => 'Тип диска: alloy, steel или forged.',
                'required' => false,
                'type' => 'string',
                'example' => 'alloy',
            ],
            'color' => [
                'description' => 'Цвет диска.',
                'required' => false,
                'type' => 'string',
                'example' => 'Silver',
            ],
            'pcd' => [
                'description' => 'PCD (сверловка).',
                'required' => false,
                'type' => 'string',
                'example' => '5x112',
            ],
            'et' => [
                'description' => 'Вылет (ET), мм.',
                'required' => false,
                'type' => 'number',
                'example' => 45,
            ],
            'hub_diameter' => [
                'description' => 'Диаметр ступицы, мм.',
                'required' => false,
                'type' => 'number',
                'example' => 66.6,
            ],
            'width' => [
                'description' => 'Ширина диска, дюймы.',
                'required' => false,
                'type' => 'number',
                'example' => 7.5,
            ],
            'diameter' => [
                'description' => 'Посадочный диаметр, дюймы.',
                'required' => false,
                'type' => 'integer',
                'example' => 17,
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

    public function rules(): array
    {
        $wheelId = $this->route('id');

        return [
            'brand_id' => ['required', 'integer', 'exists:brands,id'],
            'name' => ['required', 'string', 'max:255'],
            'supplier_id' => ['nullable', 'integer', 'exists:suppliers,id'],
            'country_id' => ['nullable', 'integer', 'exists:countries,id'],
            'ean' => [
                'nullable', 'string', 'max:50',
                Rule::unique('wheel_products', 'ean')->ignore($wheelId),
            ],
            'type' => ['nullable', Rule::in(array_column(WheelType::cases(), 'value'))],
            'color' => ['nullable', 'string', 'max:50'],
            'pcd' => ['nullable', 'string', 'max:20'],
            'et' => ['nullable', 'numeric'],
            'hub_diameter' => ['nullable', 'numeric'],
            'width' => ['nullable', 'numeric'],
            'diameter' => ['nullable', 'integer'],
            'description' => ['nullable', 'string'],
            'is_published' => ['boolean'],
            'is_bestseller' => ['boolean'],
            'is_new' => ['boolean'],
        ];
    }
}
