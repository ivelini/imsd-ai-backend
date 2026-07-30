<?php

namespace App\Http\Requests\Admin\Catalog;

use Illuminate\Foundation\Http\FormRequest;

/** Валидация загружаемого XLSX-файла импорта. */
class UploadFileRequest extends FormRequest
{
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

    public function bodyParameters(): array
    {
        return [
            'file' => [
                'description' => 'XLSX-файл импорта. Максимум 50 МБ.',
                'required' => true,
                'type' => 'file',
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
