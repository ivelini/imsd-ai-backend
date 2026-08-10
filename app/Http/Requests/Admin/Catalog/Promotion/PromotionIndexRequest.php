<?php

namespace App\Http\Requests\Admin\Catalog\Promotion;

use App\Enums\Promotion\PromotionType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/** Валидация query-параметров списка акций. */
class PromotionIndexRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:10', 'max:100'],
            'sort_by' => ['nullable', Rule::in(['id', 'name', 'type', 'value', 'starts_at', 'ends_at', 'created_at'])],
            'sort_dir' => ['nullable', Rule::in(['asc', 'desc'])],
            'search' => ['nullable', 'string', 'max:100'],
            'type' => ['nullable', Rule::in(array_column(PromotionType::cases(), 'value'))],
            'promotable_type' => ['nullable', Rule::in(['tire', 'wheel', 'brand'])],
            'is_active' => ['nullable', 'boolean'],
        ];
    }
}
