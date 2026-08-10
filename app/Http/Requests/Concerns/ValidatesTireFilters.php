<?php

namespace App\Http\Requests\Concerns;

use App\Enums\Catalog\Season;
use Illuminate\Validation\Rule;

/** Общие правила валидации фильтров шин. Используется админкой и публичным API. */
trait ValidatesTireFilters
{
    public function tireFilterRules(): array
    {
        return [
            'brand_id' => ['nullable', 'integer', 'exists:brands,id'],
            'model_id' => ['nullable', 'integer', 'exists:product_models,id'],
            'season' => ['nullable', Rule::in(array_column(Season::cases(), 'value'))],
            'width' => ['nullable', 'array'],
            'width.*' => ['integer', 'min:1', 'max:500'],
            'profile' => ['nullable', 'array'],
            'profile.*' => ['integer', 'min:1', 'max:150'],
            'diameter' => ['nullable', 'array'],
            'diameter.*' => ['string', 'max:10'],
            'load_index' => ['nullable', 'array'],
            'load_index.*' => ['string', 'max:10'],
            'speed_index' => ['nullable', 'array'],
            'speed_index.*' => ['string', 'max:5'],
            'year' => ['nullable', 'array'],
            'year.*' => ['integer', 'min:2000', 'max:2100'],
            'is_published' => ['nullable', 'boolean'],
            'is_studded' => ['nullable', 'boolean'],
            'is_runflat' => ['nullable', 'boolean'],
            'is_xl' => ['nullable', 'boolean'],
            'is_bestseller' => ['nullable', 'boolean'],
            'is_new' => ['nullable', 'boolean'],
            'city_id' => ['nullable', 'integer', 'exists:cities,id'],
        ];
    }
}
