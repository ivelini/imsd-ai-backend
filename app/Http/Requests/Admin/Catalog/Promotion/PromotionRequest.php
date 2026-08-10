<?php

namespace App\Http\Requests\Admin\Catalog\Promotion;

use App\Enums\Catalog\ProductType;
use App\Enums\Promotion\PromotionType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/** Валидация акции. */
class PromotionRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'type' => ['required', Rule::in(array_column(PromotionType::cases(), 'value'))],
            'value' => ['nullable', 'numeric', 'min:0'],
            'starts_at' => ['required', 'date'],
            'ends_at' => ['required', 'date', 'after_or_equal:starts_at'],
            'promotable_type' => ['nullable', Rule::in(array_merge(array_column(ProductType::cases(), 'value'), ['brand']))],
            'promotable_id' => ['nullable', 'integer', 'required_with:promotable_type'],
        ];
    }
}
