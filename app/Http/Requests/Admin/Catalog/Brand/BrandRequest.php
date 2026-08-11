<?php

namespace App\Http\Requests\Admin\Catalog\Brand;

use App\Enums\Catalog\BrandType;
use App\Models\Catalog\Brand\Brand;
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
                Rule::unique((new Brand)->getTable(), 'slug')->ignore($brandId),
            ],
            'logo' => ['nullable', 'file', 'image', 'mimes:jpeg,png,webp', 'max:10240'],
            'description' => ['nullable', 'string'],
            'type' => ['required', Rule::in(array_column(BrandType::cases(), 'value'))],
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
