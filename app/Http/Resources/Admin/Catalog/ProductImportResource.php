<?php

namespace App\Http\Resources\Admin\Catalog;

use App\Models\System\ProductImport;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** Ресурс статуса импорта товаров. */
final class ProductImportResource extends JsonResource
{
    /** @var ProductImport */
    public $resource;

    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        $import = $this->resource;

        return [
            'id' => $import->id,
            'type' => $import->type,
            'original_filename' => $import->original_filename,
            'status' => $import->status,
            'total_rows' => $import->total_rows,
            'processed_rows' => $import->processed_rows,
            'created_rows' => $import->created_rows,
            'updated_rows' => $import->updated_rows,
            'failed_rows' => $import->failed_rows,
            'error_message' => $import->error_message,
            'errors' => $import->errors,
            'started_at' => $import->started_at?->toIso8601String(),
            'finished_at' => $import->finished_at?->toIso8601String(),
            'created_at' => $import->created_at->toIso8601String(),
        ];
    }
}
