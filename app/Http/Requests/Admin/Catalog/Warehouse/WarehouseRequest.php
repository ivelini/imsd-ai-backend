<?php

namespace App\Http\Requests\Admin\Catalog\Warehouse;

use Illuminate\Foundation\Http\FormRequest;

/** Валидация склада. */
class WarehouseRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
        ];
    }
}
