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

    public function bodyParameters(): array
    {
        return [
            'name' => [
                'description' => 'Название акции.',
                'required' => true,
                'type' => 'string',
                'example' => 'Летняя распродажа',
            ],
            'description' => [
                'description' => 'Описание акции.',
                'required' => false,
                'type' => 'string',
            ],
            'type' => [
                'description' => 'Тип акции: percent, fixed, gift или special.',
                'required' => true,
                'type' => 'string',
                'example' => 'percent',
            ],
            'value' => [
                'description' => 'Значение акции (процент или сумма).',
                'required' => false,
                'type' => 'number',
                'example' => 15,
            ],
            'starts_at' => [
                'description' => 'Дата и время начала акции.',
                'required' => true,
                'type' => 'string',
                'example' => '2026-08-01T00:00:00',
            ],
            'ends_at' => [
                'description' => 'Дата и время окончания акции.',
                'required' => true,
                'type' => 'string',
                'example' => '2026-08-31T23:59:59',
            ],
            'promotable_type' => [
                'description' => 'Тип объекта акции: tire, wheel или brand.',
                'required' => false,
                'type' => 'string',
                'example' => 'tire',
            ],
            'promotable_id' => [
                'description' => 'ID объекта акции.',
                'required' => false,
                'type' => 'integer',
                'example' => 1,
            ],
        ];
    }
}
