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
}
