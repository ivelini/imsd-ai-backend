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
}
