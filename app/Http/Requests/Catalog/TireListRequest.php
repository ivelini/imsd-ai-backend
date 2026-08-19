<?php

namespace App\Http\Requests\Catalog;

use Illuminate\Validation\Rule;

/**
 * Валидация query-параметров публичного листинга шин.
 *
 * Контракт фильтров — как у TireFilterValuesRequest, плюс пагинация и сортировка по цене.
 */
class TireListRequest extends TireFilterValuesRequest
{
    public function rules(): array
    {
        return array_merge(parent::rules(), [
            /** Номер страницы. */
            'page' => ['nullable', 'integer', 'min:1'],
            /** Элементов на странице (10–100, по умолчанию 48). */
            'per_page' => ['nullable', 'integer', 'min:10', 'max:100'],
            /** Сортировка — только по цене города. */
            'sort_by' => ['nullable', Rule::in(['price'])],
            /** Направление сортировки. */
            'sort_dir' => ['nullable', Rule::in(['asc', 'desc'])],
        ]);
    }
}
