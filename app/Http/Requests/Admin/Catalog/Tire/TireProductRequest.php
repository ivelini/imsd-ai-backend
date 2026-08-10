<?php

namespace App\Http\Requests\Admin\Catalog\Tire;

use App\Enums\Catalog\Season;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/** Валидация создания и обновления шины. */
class TireProductRequest extends FormRequest
{
    public function rules(): array
    {
        $tireId = $this->route('id');

        return [
            'brand_id' => ['required', 'integer', 'exists:brands,id'],
            'model_id' => [
                'required', 'integer',
                Rule::exists('product_models', 'id')->where('type', 'tire'),
            ],
            'name' => ['nullable', 'string', 'max:255'],
            'supplier_id' => ['nullable', 'integer', 'exists:suppliers,id'],
            'country_id' => ['nullable', 'integer', 'exists:countries,id'],
            'ean' => [
                'nullable', 'string', 'max:50',
                Rule::unique('tire_products', 'ean')->ignore($tireId),
            ],
            'season' => ['required', Rule::in(array_column(Season::cases(), 'value'))],
            'width' => ['nullable', 'integer', 'min:100', 'max:400'],
            'profile' => ['nullable', 'integer', 'min:20', 'max:100'],
            'diameter' => ['nullable', 'string', 'max:10'],
            'load_index' => ['nullable', 'string', 'max:10'],
            'speed_index' => ['nullable', 'string', 'max:5'],
            'is_studded' => ['boolean'],
            'is_runflat' => ['boolean'],
            'is_xl' => ['boolean'],
            'year' => ['nullable', 'integer', 'min:2000', 'max:2030'],
            'description' => ['nullable', 'string'],
            'is_published' => ['boolean'],
            'is_bestseller' => ['boolean'],
            'is_new' => ['boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'brand_id.required' => 'Бренд обязателен.',
            'name.required' => 'Название модели обязательно.',
            'season.required' => 'Сезонность обязательна.',
            'ean.unique' => 'Товар с таким EAN уже существует.',
        ];
    }
}
