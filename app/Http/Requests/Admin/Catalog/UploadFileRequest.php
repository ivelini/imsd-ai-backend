<?php

namespace App\Http\Requests\Admin\Catalog;

use Illuminate\Foundation\Http\FormRequest;

/** Валидация загружаемого XLSX-файла с шинами. */
class UploadFileRequest extends FormRequest
{
    /**
     * @bodyParam file file required XLSX-файл каталога шин. Максимум 50 МБ.
     */
    public function rules(): array
    {
        return [
            'file' => [
                'required',
                'file',
                'mimes:xlsx',
                'max:51200',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'file.required' => 'Файл обязателен для загрузки.',
            'file.mimes' => 'Допустим только формат XLSX.',
            'file.max' => 'Размер файла не должен превышать 50 МБ.',
        ];
    }
}
