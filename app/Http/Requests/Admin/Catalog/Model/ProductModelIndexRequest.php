<?php

namespace App\Http\Requests\Admin\Catalog\Model;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/** Валидация query-параметров списка моделей. */
class ProductModelIndexRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'type' => ['nullable', Rule::in(['tire', 'wheel'])],
            'brand_id' => ['nullable', 'integer', 'exists:brands,id'],
            'sort_by' => ['nullable', Rule::in(['id', 'name', 'type', 'created_at'])],
            'sort_dir' => ['nullable', Rule::in(['asc', 'desc'])],
            'per_page' => ['nullable', 'integer', 'min:10', 'max:100'],
            'page' => ['nullable', 'integer', 'min:1'],
        ];
    }
}
