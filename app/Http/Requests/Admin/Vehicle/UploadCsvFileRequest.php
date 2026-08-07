<?php

namespace App\Http\Requests\Admin\Vehicle;

use Illuminate\Foundation\Http\FormRequest;

/** Валидация загружаемого CSV-файла импорта автомобилей. */
class UploadCsvFileRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'file' => [
                'required',
                'file',
                'mimes:csv,txt',
                'max:51200',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'file.required' => 'Файл обязателен для загрузки.',
            'file.mimes' => 'Допустим только формат CSV.',
            'file.max' => 'Размер файла не должен превышать 50 МБ.',
        ];
    }
}
