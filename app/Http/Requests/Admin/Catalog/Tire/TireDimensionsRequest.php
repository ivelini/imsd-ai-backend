<?php

namespace App\Http\Requests\Admin\Catalog\Tire;

use App\Http\Requests\Concerns\ValidatesTireFilters;
use Illuminate\Foundation\Http\FormRequest;

/** Валидация query-параметров для получения доступных значений фильтров шин. */
class TireDimensionsRequest extends FormRequest
{
    use ValidatesTireFilters;

    public function rules(): array
    {
        return $this->tireFilterRules();
    }

    public function bodyParameters(): array
    {
        return $this->tireFilterBodyParameters();
    }
}
