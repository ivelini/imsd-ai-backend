<?php

namespace App\Http\Requests\Admin\Delivery;

use App\Enums\Common\WeekDay;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

/** Валидация графика отгрузки склада. */
class DeliveryScheduleRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'warehouse_id' => ['required', 'integer', 'exists:warehouses,id'],
            'day_of_week' => ['required', 'integer', new Enum(WeekDay::class)],
            'cutoff_time' => ['required', 'string', 'regex:/^\d{2}:\d{2}$/'],
            'days_before' => ['required', 'integer', 'min:0'],
            'days_after' => ['required', 'integer', 'min:0'],
        ];
    }

    public function bodyParameters(): array
    {
        return [
            'warehouse_id' => [
                'description' => 'ID склада.',
                'required' => true,
                'type' => 'integer',
                'example' => 1,
            ],
            'day_of_week' => [
                'description' => 'День недели (0=пн … 6=вс).',
                'required' => true,
                'type' => 'integer',
                'example' => 1,
            ],
            'cutoff_time' => [
                'description' => 'Время отсечки (HH:MI).',
                'required' => true,
                'type' => 'string',
                'example' => '14:00',
            ],
            'days_before' => [
                'description' => 'Срок обработки при заказе до отсечки.',
                'required' => true,
                'type' => 'integer',
                'example' => 1,
            ],
            'days_after' => [
                'description' => 'Срок обработки при заказе после отсечки.',
                'required' => true,
                'type' => 'integer',
                'example' => 2,
            ],
        ];
    }
}
