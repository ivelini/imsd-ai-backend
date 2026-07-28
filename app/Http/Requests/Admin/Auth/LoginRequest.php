<?php

namespace App\Http\Requests\Admin\Auth;

use Illuminate\Foundation\Http\FormRequest;

/** Валидация входа администратора. */
class LoginRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ];
    }

    public function bodyParameters(): array
    {
        return [
            'email' => [
                'description' => 'Email администратора.',
                'required' => true,
                'type' => 'string',
                'example' => 'admin@example.com',
            ],
            'password' => [
                'description' => 'Пароль.',
                'required' => true,
                'type' => 'string',
                'example' => 'secret123',
            ],
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
