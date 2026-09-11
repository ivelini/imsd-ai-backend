<?php

namespace App\Http\Requests\Booking;

use App\Enums\Booking\CarType;
use App\Enums\Booking\WheelRadius;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/** Валидация параметров расчёта цен каталога записи. */
final class GetUnitPricesRequest extends FormRequest
{
    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'radius' => ['required', Rule::in(array_map(fn (WheelRadius $radius): int => $radius->value, WheelRadius::cases()))],
            'car_type' => ['required', Rule::in(array_map(fn (CarType $carType): string => $carType->value, CarType::bookable()))],
        ];
    }
}
