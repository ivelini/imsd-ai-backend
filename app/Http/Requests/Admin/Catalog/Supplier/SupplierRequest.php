<?php

namespace App\Http\Requests\Admin\Catalog\Supplier;

use App\Models\Catalog\Supplier\Supplier;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/** Валидация поставщика. */
class SupplierRequest extends FormRequest
{
    public function rules(): array
    {
        $supplierId = $this->route('id');

        return [
            'name' => ['required', 'string', 'max:255'],
            'code' => [
                'nullable',
                'string',
                'max:50',
                Rule::unique((new Supplier)->getTable(), 'code')->ignore($supplierId),
            ],
        ];
    }
}
