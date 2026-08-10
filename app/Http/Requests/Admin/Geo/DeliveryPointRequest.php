<?php

namespace App\Http\Requests\Admin\Geo;

use Illuminate\Foundation\Http\FormRequest;

/** Валидация создания/обновления точки выдачи. */
class DeliveryPointRequest extends FormRequest
{
    public function rules(): array
    {
        $rules = [
            'city_id' => ['required', 'integer', 'exists:cities,id'],
            'address' => ['required', 'string', 'max:500'],
            'phone' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:255'],
            'work_hours' => ['nullable', 'string', 'max:500'],
            'info' => ['nullable', 'string', 'max:1000'],
            'pickup_from_truck' => ['nullable', 'boolean'],
        ];

        if ($this->isMethod('PATCH')) {
            $rules['city_id'][0] = 'sometimes';
            $rules['address'][0] = 'sometimes';
        }

        return $rules;
    }
}
