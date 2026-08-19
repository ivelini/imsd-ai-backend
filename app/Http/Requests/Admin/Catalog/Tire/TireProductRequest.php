<?php

namespace App\Http\Requests\Admin\Catalog\Tire;

use App\Enums\Catalog\Season;
use App\Models\Catalog\Tire\TireProduct;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/** Валидация создания и обновления шины. */
class TireProductRequest extends FormRequest
{
    public function rules(): array
    {
        $tireId = $this->route('id');

        return [
            /** ID бренда. */
            'brand_id' => ['required', 'integer', 'exists:brands,id'],
            /**
             * ID модели товара (только типа tire).
             */
            'model_id' => [
                'required', 'integer',
                Rule::exists('product_models', 'id')->where('type', 'tire'),
            ],
            /** Отображаемое название (если не указано — берётся из модели). */
            'name' => ['nullable', 'string', 'max:255'],
            /** ID страны производителя. */
            'country_id' => ['nullable', 'integer', 'exists:countries,id'],
            /** EAN-код товара (уникальный в рамках каталога). */
            'ean' => [
                'nullable', 'string', 'max:50',
                Rule::unique((new TireProduct)->getTable(), 'ean')->ignore($tireId),
            ],
            /** Сезонность: summer, winter, all-season. */
            'season' => ['required', Rule::in(array_column(Season::cases(), 'value'))],
            /** Ширина профиля в мм (100–400). */
            'width' => ['nullable', 'integer', 'min:100', 'max:400'],
            /** Высота профиля в % (20–100). */
            'profile' => ['nullable', 'integer', 'min:20', 'max:100'],
            /** Посадочный диаметр в дюймах. */
            'diameter' => ['nullable', 'string', 'max:10'],
            /** Индекс нагрузки. */
            'load_index' => ['nullable', 'string', 'max:10'],
            /** Индекс скорости. */
            'speed_index' => ['nullable', 'string', 'max:5'],
            /** Шипованная. */
            'is_studded' => ['boolean'],
            /** Runflat-технология. */
            'is_runflat' => ['boolean'],
            /** Усиленная (Extra Load). */
            'is_xl' => ['boolean'],
            /** Год выпуска. */
            'year' => ['nullable', 'integer', 'min:2000', 'max:2030'],
            /** Описание товара (JSON). */
            'description' => ['nullable', 'string'],
            /** Опубликован на сайте. */
            'is_published' => ['boolean'],
            /** Хит продаж. */
            'is_bestseller' => ['boolean'],
            /** Новинка. */
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
