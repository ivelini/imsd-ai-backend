<?php

namespace App\Http\Requests\Admin\Catalog;

use App\Enums\Catalog\ProductType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/** Валидация загрузки изображения товара. */
class UploadImageRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'image' => ['required', 'image', 'mimes:jpeg,png,webp', 'max:10240'],
            'imageable_type' => ['required', Rule::in(array_column(ProductType::cases(), 'value'))],
            'imageable_id' => ['required', 'integer'],
        ];
    }

    public function messages(): array
    {
        return [
            'image.required' => 'Файл изображения обязателен.',
            'image.mimes' => 'Допустимые форматы: JPEG, PNG, WebP.',
            'image.max' => 'Размер файла не должен превышать 10 МБ.',
        ];
    }
}
