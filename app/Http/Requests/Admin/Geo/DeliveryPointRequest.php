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

    public function bodyParameters(): array
    {
        return [
            'city_id' => [
                'description' => 'ID города.',
                'required' => true,
                'type' => 'integer',
                'example' => 1,
            ],
            'address' => [
                'description' => 'Адрес точки выдачи.',
                'required' => true,
                'type' => 'string',
                'example' => 'ул. Ленина, 10',
            ],
            'phone' => [
                'description' => 'Телефон.',
                'required' => false,
                'type' => 'string',
                'example' => '+7 (351) 000-00-00',
            ],
            'email' => [
                'description' => 'Email.',
                'required' => false,
                'type' => 'string',
                'example' => 'info@example.ru',
            ],
            'work_hours' => [
                'description' => 'Режим работы.',
                'required' => false,
                'type' => 'string',
                'example' => 'пн-пт 9:00–18:00, сб 10:00–15:00',
            ],
            'info' => [
                'description' => 'Дополнительная информация.',
                'required' => false,
                'type' => 'string',
                'example' => 'Вход со двора',
            ],
            'pickup_from_truck' => [
                'description' => 'Возможен самовывоз из фуры.',
                'required' => false,
                'type' => 'boolean',
                'example' => false,
            ],
        ];
    }
}
