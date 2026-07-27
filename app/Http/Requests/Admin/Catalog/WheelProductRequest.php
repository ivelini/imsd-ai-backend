<?php

namespace App\Http\Requests\Admin\Catalog;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/** Валидация диска. */
class WheelProductRequest extends FormRequest
{
    public function rules(): array
    {
        $wheelId = $this->route('id');

        return [
            'brand_id' => ['required', 'integer', 'exists:brands,id'],
            'name' => ['required', 'string', 'max:255'],
            'supplier_id' => ['nullable', 'integer', 'exists:suppliers,id'],
            'country_id' => ['nullable', 'integer', 'exists:countries,id'],
            'ean' => [
                'nullable', 'string', 'max:50',
                Rule::unique('wheel_products', 'ean')->ignore($wheelId),
            ],
            'type' => ['nullable', Rule::in(['alloy', 'steel', 'forged'])],
            'color' => ['nullable', 'string', 'max:50'],
            'pcd' => ['nullable', 'string', 'max:20'],
            'et' => ['nullable', 'numeric'],
            'hub_diameter' => ['nullable', 'numeric'],
            'width' => ['nullable', 'numeric'],
            'diameter' => ['nullable', 'integer'],
            'description' => ['nullable', 'string'],
            'is_published' => ['boolean'],
            'is_bestseller' => ['boolean'],
            'is_new' => ['boolean'],
        ];
    }
}
