<?php

namespace App\Http\Requests\Admin\Catalog\MarkupRule;

use Illuminate\Foundation\Http\FormRequest;

/** Валидация правила наценки склада. */
class MarkupRuleRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'warehouse_id' => ['required', 'integer', 'exists:warehouses,id'],
            'price_from' => ['required', 'numeric', 'min:0'],
            'price_to' => ['required', 'numeric', 'min:0'],
            'coefficient' => ['required', 'numeric', 'min:1'],
        ];
    }

    public function bodyParameters(): array
    {
        return [
            'warehouse_id' => [
                'description' => 'ID склада.',
                'required' => true,
                'type' => 'integer',
                'example' => 1,
            ],
            'price_from' => [
                'description' => 'Начальная цена диапазона.',
                'required' => true,
                'type' => 'number',
                'example' => 1000,
            ],
            'price_to' => [
                'description' => 'Конечная цена диапазона.',
                'required' => true,
                'type' => 'number',
                'example' => 5000,
            ],
            'coefficient' => [
                'description' => 'Коэффициент наценки.',
                'required' => true,
                'type' => 'number',
                'example' => 1.5,
            ],
        ];
    }
}
