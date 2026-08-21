<?php

namespace App\Http\Requests\Catalog;

use App\Enums\Catalog\DeliveryDaysType;
use App\Enums\Catalog\WheelType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/** Валидация query-параметров активного фильтра дисков (публичный контракт). */
class WheelFilterValuesRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            /** Ширина диска в дюймах (массив). */
            'width' => ['nullable', 'array'],
            'width.*' => ['numeric', 'min:1', 'max:20'],
            /** Посадочный диаметр в дюймах (массив). */
            'diameter' => ['nullable', 'array'],
            'diameter.*' => ['integer', 'min:1', 'max:50'],
            /** Разболтовка PCD (массив, строка «5*112»). */
            'pcd' => ['nullable', 'array'],
            'pcd.*' => ['string', 'max:20'],
            /** Вылет ET (массив, строка как в БД, «38.0»). */
            'et' => ['nullable', 'array'],
            'et.*' => ['string', 'max:10'],
            /** Диаметр ступицы DIA (массив, строка как в БД, «66.1»). */
            'hub_diameter' => ['nullable', 'array'],
            'hub_diameter.*' => ['string', 'max:10'],
            /** Материал: alloy, steel, forged. */
            'type' => ['nullable', Rule::in(array_column(WheelType::cases(), 'value'))],
            /** Цвет. */
            'color' => ['nullable', 'string', 'max:50'],
            /** Бренд (slug). */
            'brand' => ['nullable', 'string', 'max:255', 'exists:brands,slug'],
            /** Страна бренда (slug). */
            'country' => ['nullable', 'string', 'max:255', 'exists:countries,slug'],
            /** Бакеты срока доставки (массив). */
            'delivery' => ['nullable', 'array'],
            'delivery.*' => ['string', Rule::in(array_column(DeliveryDaysType::cases(), 'value'))],
            /** Цена от. */
            'price_min' => ['nullable', 'numeric', 'min:0'],
            /** Цена до. */
            'price_max' => ['nullable', 'numeric', 'min:0'],
            /** ID города для расчёта цены и доставки (по умолчанию — из config/shop.php). */
            'city_id' => ['nullable', 'integer', 'exists:cities,id'],
            /** Город слагом (без exists: несуществующий слаг не 422 — фолбэк на дефолтный город). */
            'city' => ['nullable', 'string', 'max:255'],
        ];
    }
}
