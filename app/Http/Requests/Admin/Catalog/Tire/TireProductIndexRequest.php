<?php

namespace App\Http\Requests\Admin\Catalog\Tire;

use App\Enums\Catalog\Season;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/** Валидация query-параметров списка шин. */
class TireProductIndexRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:10', 'max:100'],
            'sort_by' => ['nullable', Rule::in(['id', 'name', 'ean', 'season', 'width', 'profile', 'diameter', 'load_index', 'speed_index', 'year', 'is_published', 'created_at'])],
            'sort_dir' => ['nullable', Rule::in(['asc', 'desc'])],
            'search' => ['nullable', 'string', 'max:100'],
            'brand_id' => ['nullable', 'integer', 'exists:brands,id'],
            'season' => ['nullable', Rule::in(array_column(Season::cases(), 'value'))],
            'is_published' => ['nullable', 'boolean'],
            'is_studded' => ['nullable', 'boolean'],
            'is_runflat' => ['nullable', 'boolean'],
            'is_xl' => ['nullable', 'boolean'],
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
                'description' => 'Поле сортировки: id, name, ean, season, width, profile, diameter, load_index, speed_index, year, is_published, created_at.',
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
                'example' => 'Winter',
            ],
            'brand_id' => [
                'description' => 'ID бренда для фильтрации.',
                'required' => false,
                'type' => 'integer',
                'example' => 1,
            ],
            'season' => [
                'description' => 'Сезонность: summer, winter, all-season.',
                'required' => false,
                'type' => 'string',
                'example' => 'winter',
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
        ];
    }
}
