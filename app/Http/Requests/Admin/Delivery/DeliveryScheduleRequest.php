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
}
