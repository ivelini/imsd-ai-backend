<?php

namespace App\Http\Requests\Admin\Catalog\Image;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/** Валидация query-параметров списка изображений. */
class ImageIndexRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'imageable_type' => ['required', Rule::in(['tire', 'wheel'])],
            'imageable_id' => ['required', 'integer'],
        ];
    }

    public function bodyParameters(): array
    {
        return [
            'imageable_type' => [
                'description' => 'Тип товара: tire или wheel.',
                'required' => true,
                'type' => 'string',
                'example' => 'tire',
            ],
            'imageable_id' => [
                'description' => 'ID товара.',
                'required' => true,
                'type' => 'integer',
                'example' => 1,
            ],
        ];
    }
}
