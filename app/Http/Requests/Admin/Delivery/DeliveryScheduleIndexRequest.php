<?php

namespace App\Http\Requests\Admin\Delivery;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/** Валидация query-параметров списка графиков отгрузки. */
class DeliveryScheduleIndexRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:10', 'max:100'],
            'sort_by' => ['nullable', Rule::in(['id', 'warehouse_id', 'day_of_week', 'created_at'])],
            'sort_dir' => ['nullable', Rule::in(['asc', 'desc'])],
            'warehouse_id' => ['nullable', 'integer', 'exists:warehouses,id'],
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
                'description' => 'Поле сортировки: id, warehouse_id, day_of_week, created_at.',
                'required' => false,
                'type' => 'string',
                'example' => 'warehouse_id',
            ],
            'sort_dir' => [
                'description' => 'Направление сортировки: asc или desc.',
                'required' => false,
                'type' => 'string',
                'example' => 'asc',
            ],
            'warehouse_id' => [
                'description' => 'ID склада для фильтрации.',
                'required' => false,
                'type' => 'integer',
                'example' => 1,
            ],
        ];
    }
}
