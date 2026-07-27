<?php

namespace App\Http\Requests\Admin\Catalog;

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
}
