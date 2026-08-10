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
}
