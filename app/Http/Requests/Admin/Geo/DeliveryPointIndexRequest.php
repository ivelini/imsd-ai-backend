<?php

namespace App\Http\Requests\Admin\Geo;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/** Валидация query-параметров списка точек выдачи. */
class DeliveryPointIndexRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:10', 'max:100'],
            'sort_by' => ['nullable', Rule::in(['id', 'city_id', 'address', 'created_at'])],
            'sort_dir' => ['nullable', Rule::in(['asc', 'desc'])],
            'city_id' => ['nullable', 'integer', 'exists:cities,id'],
            'search' => ['nullable', 'string', 'max:100'],
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
                'description' => 'Поле сортировки: id, city_id, address, created_at.',
                'required' => false,
                'type' => 'string',
                'example' => 'city_id',
            ],
            'sort_dir' => [
                'description' => 'Направление сортировки: asc или desc.',
                'required' => false,
                'type' => 'string',
                'example' => 'asc',
            ],
            'city_id' => [
                'description' => 'ID города для фильтрации.',
                'required' => false,
                'type' => 'integer',
                'example' => 1,
            ],
            'search' => [
                'description' => 'Поиск по адресу или телефону.',
                'required' => false,
                'type' => 'string',
                'example' => 'Ленина',
            ],
        ];
    }
}
