<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

/** Валидация запроса списка уведомлений. */
final class NotificationIndexRequest extends FormRequest
{
    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'unread_only' => 'boolean',
            'per_page' => 'integer|min:1|max:100',
            'page' => 'integer|min:1',
        ];
    }
}
