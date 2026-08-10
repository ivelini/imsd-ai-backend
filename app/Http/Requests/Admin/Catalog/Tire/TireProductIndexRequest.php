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
            /** Номер страницы. */
            'page' => ['nullable', 'integer', 'min:1'],
            /** Элементов на странице (10–100). */
            'per_page' => ['nullable', 'integer', 'min:10', 'max:100'],
            /**
             * Поле сортировки.
             *
             * @var 'id'|'name'|'ean'|'season'|'width'|'profile'|'diameter'|'load_index'|'speed_index'|'year'|'is_published'|'created_at'
             */
            'sort_by' => ['nullable', Rule::in(['id', 'name', 'ean', 'season', 'width', 'profile', 'diameter', 'load_index', 'speed_index', 'year', 'is_published', 'created_at'])],
            /**
             * Направление сортировки.
             *
             * @var 'asc'|'desc'
             */
            'sort_dir' => ['nullable', Rule::in(['asc', 'desc'])],
            /** Поиск по названию модели или EAN. */
            'search' => ['nullable', 'string', 'max:100'],
        ]);
    }
}
