<?php

namespace App\Http\Requests\Admin\Geo;

use Illuminate\Foundation\Http\FormRequest;

/** Валидация query-параметров списка городов. */
class CityIndexRequest extends FormRequest
{
    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:100'],
            'region_code' => ['nullable', 'string', 'max:10'],
        ];
    }

    /** @return array<string, mixed> */
}
