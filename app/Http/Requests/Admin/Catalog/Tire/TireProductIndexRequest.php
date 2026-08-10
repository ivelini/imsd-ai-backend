<?php

namespace App\Http\Requests\Admin\Catalog\Tire;

use App\Http\Requests\Concerns\ValidatesTireFilters;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/** Валидация query-параметров списка шин. */
class TireProductIndexRequest extends FormRequest
{
    use ValidatesTireFilters;

    public function rules(): array
    {
        return array_merge($this->tireFilterRules(), [
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:10', 'max:100'],
            'sort_by' => ['nullable', Rule::in(['id', 'name', 'ean', 'season', 'width', 'profile', 'diameter', 'load_index', 'speed_index', 'year', 'is_published', 'created_at'])],
            'sort_dir' => ['nullable', Rule::in(['asc', 'desc'])],
            'search' => ['nullable', 'string', 'max:100'],
        ]);
    }
}
