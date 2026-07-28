<?php

namespace App\Http\Requests\Admin\Catalog;

use App\Enums\Promotion\PromotionType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/** Валидация query-параметров списка акций. */
class PromotionIndexRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:10', 'max:100'],
            'sort_by' => ['nullable', Rule::in(['id', 'name', 'type', 'value', 'starts_at', 'ends_at', 'created_at'])],
            'sort_dir' => ['nullable', Rule::in(['asc', 'desc'])],
            'search' => ['nullable', 'string', 'max:100'],
            'type' => ['nullable', Rule::in(array_column(PromotionType::cases(), 'value'))],
            'promotable_type' => ['nullable', Rule::in(['tire', 'wheel', 'brand'])],
            'is_active' => ['nullable', 'boolean'],
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
                'description' => 'Поле сортировки: id, name, type, value, starts_at, ends_at, created_at.',
                'required' => false,
                'type' => 'string',
                'example' => 'starts_at',
            ],
            'sort_dir' => [
                'description' => 'Направление сортировки: asc или desc.',
                'required' => false,
                'type' => 'string',
                'example' => 'desc',
            ],
            'search' => [
                'description' => 'Поиск по названию акции.',
                'required' => false,
                'type' => 'string',
                'example' => 'распродажа',
            ],
            'type' => [
                'description' => 'Тип акции: percent, fixed, gift, special.',
                'required' => false,
                'type' => 'string',
                'example' => 'percent',
            ],
            'promotable_type' => [
                'description' => 'Тип объекта акции: tire, wheel, brand.',
                'required' => false,
                'type' => 'string',
                'example' => 'tire',
            ],
            'is_active' => [
                'description' => 'Фильтр: сейчас активна (starts_at <= now <= ends_at).',
                'required' => false,
                'type' => 'boolean',
                'example' => true,
            ],
        ];
    }
}
