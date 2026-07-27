<?php

namespace App\Http\Requests\Admin\Catalog;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/** Валидация создания и обновления бренда. */
class BrandRequest extends FormRequest
{
    public function rules(): array
    {
        $brandId = $this->route('id');

        return [
            'name' => ['required', 'string', 'max:255'],
            'slug' => [
                'required',
                'string',
                'max:255',
                Rule::unique('brands', 'slug')->ignore($brandId),
            ],
            'logo' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'type' => ['required', Rule::in(['tire', 'wheel', 'both'])],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Название бренда обязательно.',
            'slug.unique' => 'Такой slug уже используется.',
            'type.in' => 'Тип должен быть: tire, wheel или both.',
        ];
    }
}
