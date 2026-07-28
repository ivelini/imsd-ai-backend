<?php

namespace App\Http\Requests\Admin\Catalog;

use App\Enums\Catalog\WheelType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/** Валидация query-параметров списка дисков. */
class WheelProductIndexRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:10', 'max:100'],
            'sort_by' => ['nullable', Rule::in(['id', 'name', 'ean', 'type', 'color', 'pcd', 'et', 'hub_diameter', 'width', 'diameter', 'is_published', 'created_at'])],
            'sort_dir' => ['nullable', Rule::in(['asc', 'desc'])],
            'search' => ['nullable', 'string', 'max:100'],
            'brand_id' => ['nullable', 'integer', 'exists:brands,id'],
            'type' => ['nullable', Rule::in(array_column(WheelType::cases(), 'value'))],
            'color' => ['nullable', 'string', 'max:50'],
            'is_published' => ['nullable', 'boolean'],
        ];
    }

    public function bodyParameters(): array
    {
        return [
            'page' => [
                'description' => 'Номер страницы.',
                'required' => false,
                'type' => 'integer',
                'example' => 1,
            ],
            'per_page' => [
                'description' => 'Элементов на странице (10–100).',
                'required' => false,
                'type' => 'integer',
                'example' => 50,
            ],
            'sort_by' => [
                'description' => 'Поле сортировки: id, name, ean, type, color, pcd, et, hub_diameter, width, diameter, is_published, created_at.',
                'required' => false,
                'type' => 'string',
                'example' => 'created_at',
            ],
            'sort_dir' => [
                'description' => 'Направление сортировки: asc или desc.',
                'required' => false,
                'type' => 'string',
                'example' => 'desc',
            ],
            'search' => [
                'description' => 'Поиск по названию модели или EAN.',
                'required' => false,
                'type' => 'string',
                'example' => 'ANNA',
            ],
            'brand_id' => [
                'description' => 'ID бренда для фильтрации.',
                'required' => false,
                'type' => 'integer',
                'example' => 1,
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
            'is_published' => [
                'description' => 'Опубликован на сайте.',
                'required' => false,
                'type' => 'boolean',
                'example' => true,
            ],
        ];
    }
}
