<?php

namespace App\Http\Requests\Booking;

use App\Enums\Booking\CarType;
use App\Enums\Booking\WheelRadius;
use App\Services\Booking\PriceCalculator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/** Валидация формата данных подтверждения записи: телефон, код, контакты, слот, состав. */
final class ConfirmBookingRequest extends FormRequest
{
    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'phone' => 'required|string|max:30',
            'code' => 'required|string|size:4',
            'name' => 'required|string|max:255',
            'plate' => 'nullable|string|max:20',
            'date' => 'required|date_format:Y-m-d',
            'hour' => 'required|integer|between:0,23',
            'radius' => ['required', Rule::in(array_map(fn (WheelRadius $radius): int => $radius->value, WheelRadius::cases()))],
            'car_type' => ['required', Rule::in(CarType::bookableValues())],
            'service_ids' => 'required|array|min:1',
            'service_ids.*' => 'integer|exists:booking_services,id',
            'quantities' => 'array',
            'quantities.*' => 'integer|between:'.PriceCalculator::MIN_QUANTITY.','.PriceCalculator::MAX_QUANTITY,
        ];
    }
}
