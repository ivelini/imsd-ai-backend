<?php

namespace App\Http\Requests\Admin\Auth;

use Illuminate\Foundation\Http\FormRequest;

/** Валидация входа администратора. */
class LoginRequest extends FormRequest
{
    /**
     * @bodyParam email string required Email администратора.
     * @bodyParam password string required Пароль.
     */
    public function rules(): array
    {
        return [
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'email.required' => 'Email обязателен.',
            'email.email' => 'Некорректный формат email.',
            'password.required' => 'Пароль обязателен.',
        ];
    }
}
