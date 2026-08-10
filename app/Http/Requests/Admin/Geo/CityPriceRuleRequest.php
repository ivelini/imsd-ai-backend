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
}
