<?php

namespace App\Http\Requests\Admin\Catalog\Wheel;

use App\Http\Requests\Concerns\ValidatesWheelFilters;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/** Валидация query-параметров списка дисков. */
class WheelProductIndexRequest extends FormRequest
{
    use ValidatesWheelFilters;

    public function rules(): array
    {
        return array_merge($this->wheelFilterRules(), [
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:10', 'max:100'],
            'sort_by' => ['nullable', Rule::in(['id', 'name', 'ean', 'type', 'color', 'pcd', 'et', 'hub_diameter', 'width', 'diameter', 'is_published', 'created_at'])],
            'sort_dir' => ['nullable', Rule::in(['asc', 'desc'])],
            'search' => ['nullable', 'string', 'max:100'],
        ]);
    }

    public function bodyParameters(): array
    {
        return array_merge($this->wheelFilterBodyParameters(), [
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
        ]);
    }
}
