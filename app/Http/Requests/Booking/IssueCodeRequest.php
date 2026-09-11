<?php

namespace App\Http\Requests\Booking;

use Illuminate\Foundation\Http\FormRequest;

/** Валидация телефона при запросе SMS-кода. */
final class IssueCodeRequest extends FormRequest
{
    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'phone' => 'required|string|max:30',
        ];
    }
}
