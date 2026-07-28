<?php

namespace App\Http\Requests\Admin\Catalog;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/** Валидация поставщика. */
class SupplierRequest extends FormRequest
{
    public function rules(): array
    {
        $supplierId = $this->route('id');

        return [
            'name' => ['required', 'string', 'max:255'],
            'code' => [
                'nullable',
                'string',
                'max:50',
                Rule::unique('suppliers', 'code')->ignore($supplierId),
            ],
        ];
    }

    public function bodyParameters(): array
    {
        return [
            'name' => [
                'description' => 'Название поставщика.',
                'required' => true,
                'type' => 'string',
                'example' => 'ООО Шины-Опт',
            ],
            'code' => [
                'description' => 'Код поставщика.',
                'required' => false,
                'type' => 'string',
                'example' => 'SUP-001',
            ],
        ];
    }
}
