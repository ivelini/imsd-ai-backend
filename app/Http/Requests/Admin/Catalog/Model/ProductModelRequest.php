<?php

namespace App\Http\Requests\Admin\Catalog\Model;

use App\Models\Catalog\Model\ProductModel;
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
                Rule::unique((new ProductModel)->getTable(), 'slug')
                    ->where('brand_id', $this->input('brand_id'))
                    ->ignore($modelId),
            ],
            'description' => ['nullable', 'string'],
            'image' => ['nullable', 'file', 'image', 'mimes:jpeg,png,webp', 'max:10240'],
            'type' => ['required', Rule::in(['tire', 'wheel'])],
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
