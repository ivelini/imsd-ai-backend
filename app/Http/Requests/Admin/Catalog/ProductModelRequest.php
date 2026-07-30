<?php

namespace App\Http\Requests\Admin\Catalog;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/** Валидация создания и обновления модели товара. */
class ProductModelRequest extends FormRequest
{
    public function rules(): array
    {
        $modelId = $this->route('id');

        return [
            'brand_id' => ['required', 'integer', 'exists:brands,id'],
            'name' => ['required', 'string', 'max:255'],
            'slug' => [
                'required',
                'string',
                'max:255',
                Rule::unique('product_models', 'slug')
                    ->where('brand_id', $this->input('brand_id'))
                    ->ignore($modelId),
            ],
            'description' => ['nullable', 'string'],
            'image' => ['nullable', 'file', 'image', 'mimes:jpeg,png,webp', 'max:10240'],
            'type' => ['required', Rule::in(['tire', 'wheel'])],
        ];
    }

    public function bodyParameters(): array
    {
        return [
            'brand_id' => [
                'description' => 'ID бренда.',
                'required' => true,
                'type' => 'integer',
                'example' => 1,
            ],
            'name' => [
                'description' => 'Название модели.',
                'required' => true,
                'type' => 'string',
                'example' => 'A503',
            ],
            'slug' => [
                'description' => 'URL-псевдоним (бренд-модель).',
                'required' => true,
                'type' => 'string',
                'example' => 'cordiant-a503',
            ],
            'description' => [
                'description' => 'Общее описание линейки модели.',
                'required' => false,
                'type' => 'string',
            ],
            'image' => [
                'description' => 'Файл изображения модели (jpeg, png, webp, до 10 МБ).',
                'required' => false,
                'type' => 'file',
            ],
            'type' => [
                'description' => 'Тип: tire или wheel.',
                'required' => true,
                'type' => 'string',
                'example' => 'tire',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'brand_id.required' => 'Бренд обязателен.',
            'name.required' => 'Название модели обязательно.',
            'slug.unique' => 'Такой slug уже используется для этого бренда.',
            'type.in' => 'Тип должен быть: tire или wheel.',
        ];
    }
}
