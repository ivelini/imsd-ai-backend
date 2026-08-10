<?php

namespace App\Http\Requests\Admin\Catalog\Wheel;

use App\Http\Requests\Concerns\ValidatesWheelFilters;
use Illuminate\Foundation\Http\FormRequest;

/** Валидация query-параметров для получения доступных значений фильтров дисков. */
class WheelDimensionsRequest extends FormRequest
{
    use ValidatesWheelFilters;

    public function rules(): array
    {
        return $this->wheelFilterRules();
    }
}
