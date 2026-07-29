<?php

namespace App\Http\Requests\Admin\Catalog;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/** Валидация query-параметров списка товаров каталога. */
class CatalogProductIndexRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'type' => ['nullable', Rule::in(['tire', 'wheel'])],
            'brand_id' => ['nullable', 'integer', 'exists:brands,id'],
            'is_published' => ['nullable', 'boolean'],
            'search' => ['nullable', 'string', 'max:100'],
            'sort_by' => ['nullable', Rule::in(['name', 'brand_name', 'created_at', 'ean'])],
            'sort_dir' => ['nullable', Rule::in(['asc', 'desc'])],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:10', 'max:100'],
        ];
    }

    public function bodyParameters(): array
    {
        return [
            'type' => [
                'description' => 'Фильтр по типу товара: tire или wheel.',
                'required' => false,
                'type' => 'string',
                'example' => 'tire',
            ],
            'brand_id' => [
                'description' => 'Фильтр по ID бренда.',
                'required' => false,
                'type' => 'integer',
                'example' => 1,
            ],
            'is_published' => [
                'description' => 'Фильтр по статусу публикации.',
                'required' => false,
                'type' => 'boolean',
                'example' => true,
            ],
            'search' => [
                'description' => 'Поиск по названию или EAN.',
                'required' => false,
                'type' => 'string',
                'example' => 'Michelin',
            ],
            'sort_by' => [
                'description' => 'Поле сортировки: name, brand_name, created_at, ean.',
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
        ];
    }
}
