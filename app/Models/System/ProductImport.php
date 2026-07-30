<?php

namespace App\Models\System;

use App\Enums\Import\ImportType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/** Аудит импорта товаров (шины/диски): статус, статистика, ошибки.
 *
 * @property-read Carbon|null $started_at
 * @property-read Carbon|null $finished_at
 */
class ProductImport extends Model
{
    protected $table = 'product_imports';

    protected $fillable = [
        'original_filename',
        'type',
        'status',
        'total_rows',
        'processed_rows',
        'created_rows',
        'updated_rows',
        'failed_rows',
        'error_message',
        'errors',
        'started_at',
        'finished_at',
    ];

    protected function casts(): array
    {
        return [
            'type' => ImportType::class,
            'total_rows' => 'integer',
            'processed_rows' => 'integer',
            'created_rows' => 'integer',
            'updated_rows' => 'integer',
            'failed_rows' => 'integer',
            'errors' => 'array',
            'started_at' => 'datetime',
            'finished_at' => 'datetime',
        ];
    }
}
