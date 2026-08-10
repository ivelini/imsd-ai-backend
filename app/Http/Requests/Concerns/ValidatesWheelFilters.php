<?php

namespace App\Http\Requests\Concerns;

use App\Enums\Catalog\WheelType;
use Illuminate\Validation\Rule;

/** Общие правила валидации фильтров дисков. Используется админкой и публичным API. */
trait ValidatesWheelFilters
{
    public function wheelFilterRules(): array
    {
        return [
            'brand_id' => ['nullable', 'integer', 'exists:brands,id'],
            'model_id' => ['nullable', 'integer', 'exists:product_models,id'],
            'type' => ['nullable', Rule::in(array_column(WheelType::cases(), 'value'))],
            'color' => ['nullable', 'string', 'max:50'],
            'width' => ['nullable', 'array'],
            'width.*' => ['numeric', 'min:1', 'max:500'],
            'diameter' => ['nullable', 'array'],
            'diameter.*' => ['integer', 'min:1', 'max:50'],
            'pcd' => ['nullable', 'array'],
            'pcd.*' => ['string', 'max:20'],
            'et' => ['nullable', 'array'],
            'et.*' => ['numeric'],
            'hub_diameter' => ['nullable', 'array'],
            'hub_diameter.*' => ['numeric'],
            'is_published' => ['nullable', 'boolean'],
            'is_bestseller' => ['nullable', 'boolean'],
            'is_new' => ['nullable', 'boolean'],
            'city_id' => ['nullable', 'integer', 'exists:cities,id'],
        ];
    }
}
