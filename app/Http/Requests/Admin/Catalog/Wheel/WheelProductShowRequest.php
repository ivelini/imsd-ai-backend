<?php

namespace App\Http\Requests\Admin\Catalog\Wheel;

use Illuminate\Foundation\Http\FormRequest;

/** Валидация query-параметра для получения диска. */
class WheelProductShowRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            /** ID города для расчёта доставки. */
            'city_id' => ['nullable', 'integer', 'exists:cities,id'],
        ];
    }
}
