<?php

namespace App\Http\Controllers\Admin;

use App\Http\Resources\Admin\Catalog\ProductImportResource;
use App\Models\System\ProductImport;

/** Универсальный статус асинхронного импорта (шины, диски, точки выдачи). */
final readonly class ImportController
{
    /**
     * Статус импорта по ID.
     *
     * Единый эндпоинт для всех типов импорта: tire, wheel, point.
     */
    public function show(int $id): ProductImportResource
    {
        return new ProductImportResource(
            ProductImport::findOrFail($id)
        );
    }
}
