<?php

namespace App\Http\Requests\Catalog;

use App\Enums\Catalog\DeliveryDaysType;
use App\Enums\Catalog\Season;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/** Валидация query-параметров активного фильтра шин (публичный контракт). */
class TireFilterValuesRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            /** Ширина профиля в мм. */
            'width' => ['nullable', 'array'],
            'width.*' => ['integer', 'min:1', 'max:500'],
            /** Высота профиля в %. */
            'profile' => ['nullable', 'array'],
            'profile.*' => ['integer', 'min:1', 'max:150'],
            /** Посадочный диаметр в дюймах. */
            'diameter' => ['nullable', 'array'],
            'diameter.*' => ['string', 'max:10'],
            /** Сезонность: summer, winter, all-season. */
            'season' => ['nullable', Rule::in(array_column(Season::cases(), 'value'))],
            /** Шипованность. */
            'studded' => ['nullable', Rule::in(['studded', 'not_studded'])],
            /** Бренд (slug). */
            'brand' => ['nullable', 'string', 'max:255', 'exists:brands,slug'],
            /** Страна бренда (slug). */
            'country' => ['nullable', 'string', 'max:255', 'exists:countries,slug'],
            /** Бакеты срока доставки (массив). */
            'delivery' => ['nullable', 'array'],
            'delivery.*' => ['required', Rule::in(array_column(DeliveryDaysType::cases(), 'value'))],
            /** Цена от. */
            'price_min' => ['nullable', 'numeric', 'min:0'],
            /** Цена до. */
            'price_max' => ['nullable', 'numeric', 'min:0'],
            /** ID города для расчёта цены и доставки (по умолчанию — из config/shop.php). */
            'city_id' => ['nullable', 'integer', 'exists:cities,id'],
        ];
    }
}
