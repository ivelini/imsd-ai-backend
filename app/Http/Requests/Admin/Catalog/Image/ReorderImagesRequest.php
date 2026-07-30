<?php

namespace App\Http\Requests\Admin\Catalog\Image;

use Illuminate\Foundation\Http\FormRequest;

/** Валидация изменения порядка изображений. */
class ReorderImagesRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'ids' => ['required', 'array'],
            'ids.*' => ['integer'],
        ];
    }

    public function bodyParameters(): array
    {
        return [
            'ids' => [
                'description' => 'Массив ID изображений в новом порядке.',
                'required' => true,
                'type' => 'array',
                'example' => [3, 1, 2],
            ],
        ];
    }
}
