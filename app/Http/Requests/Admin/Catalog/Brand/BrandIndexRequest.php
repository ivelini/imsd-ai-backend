<?php

namespace App\Http\Requests\Admin\Catalog\Brand;

use App\Enums\Catalog\BrandType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/** Валидация query-параметров списка брендов. */
class BrandIndexRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:10', 'max:100'],
            'sort_by' => ['nullable', Rule::in(['id', 'name', 'type', 'created_at'])],
            'sort_dir' => ['nullable', Rule::in(['asc', 'desc'])],
            'search' => ['nullable', 'string', 'max:100'],
            'type' => ['nullable', Rule::in(array_column(BrandType::cases(), 'value'))],
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
                'description' => 'Поле сортировки: id, name, type, created_at.',
                'required' => false,
                'type' => 'string',
                'example' => 'name',
            ],
            'sort_dir' => [
                'description' => 'Направление сортировки: asc или desc.',
                'required' => false,
                'type' => 'string',
                'example' => 'asc',
            ],
            'search' => [
                'description' => 'Поиск по названию бренда.',
                'required' => false,
                'type' => 'string',
                'example' => 'Cordiant',
            ],
            'type' => [
                'description' => 'Тип товаров бренда: tire, wheel, both.',
                'required' => false,
                'type' => 'string',
                'example' => 'tire',
            ],
        ];
    }
}
