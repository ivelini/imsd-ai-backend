<?php

namespace App\Http\Requests\Admin\Geo;

use Illuminate\Foundation\Http\FormRequest;

/** Валидация создания/обновления правила наценки города. */
class CityPriceRuleRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'city_id' => ['required', 'integer', 'exists:cities,id'],
            'price_from' => ['required', 'numeric', 'min:0'],
            'price_to' => ['required', 'numeric', 'min:0', 'gte:price_from'],
            'markup' => ['required', 'numeric', 'min:0'],
        ];
    }

    public function bodyParameters(): array
    {
        return [
            'city_id' => [
                'description' => 'ID города.',
                'required' => true,
                'type' => 'integer',
                'example' => 1,
            ],
            'price_from' => [
                'description' => 'Начало диапазона цены (включительно).',
                'required' => true,
                'type' => 'number',
                'example' => 0,
            ],
            'price_to' => [
                'description' => 'Конец диапазона цены (включительно).',
                'required' => true,
                'type' => 'number',
                'example' => 5000,
            ],
            'markup' => [
                'description' => 'Наценка в рублях.',
                'required' => true,
                'type' => 'number',
                'example' => 300,
            ],
        ];
    }
}
