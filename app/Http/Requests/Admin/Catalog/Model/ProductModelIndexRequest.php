<?php

namespace App\Http\Requests\Admin\Catalog\Model;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/** Валидация query-параметров списка моделей. */
class ProductModelIndexRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'type' => ['nullable', Rule::in(['tire', 'wheel'])],
            'brand_id' => ['nullable', 'integer', 'exists:brands,id'],
            'sort_by' => ['nullable', Rule::in(['id', 'name', 'type', 'created_at'])],
            'sort_dir' => ['nullable', Rule::in(['asc', 'desc'])],
            'per_page' => ['nullable', 'integer', 'min:10', 'max:100'],
            'page' => ['nullable', 'integer', 'min:1'],
        ];
    }

    public function queryParameters(): array
    {
        return [
            'type' => [
                'description' => 'Фильтр по типу: tire или wheel.',
                'required' => false,
                'type' => 'string',
                'example' => 'tire',
            ],
            'brand_id' => [
                'description' => 'Фильтр по бренду.',
                'required' => false,
                'type' => 'integer',
                'example' => 1,
            ],
            'sort_by' => [
                'description' => 'Поле сортировки: id, name, type, created_at.',
                'required' => false,
                'type' => 'string',
            ],
            'sort_dir' => [
                'description' => 'Направление: asc или desc.',
                'required' => false,
                'type' => 'string',
            ],
            'per_page' => [
                'description' => 'Элементов на странице (10–100).',
                'required' => false,
                'type' => 'integer',
            ],
        ];
    }
}
