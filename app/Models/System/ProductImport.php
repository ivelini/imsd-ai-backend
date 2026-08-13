<?php

namespace App\Models\System;

use App\Enums\Import\ImportState;
use App\Enums\Import\ImportType;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

/**
 * Аудит импорта товаров (шины/диски): статус, статистика, ошибки.
 *
 * @property int $id
 * @property string $original_filename
 * @property ImportType $type
 * @property ImportState $status
 * @property int $total_rows
 * @property int $processed_rows
 * @property int $created_rows
 * @property int $updated_rows
 * @property int $failed_rows
 * @property string|null $error_message
 * @property array|null $errors
 * @property array|null $affected_stock_ids
 * @property Carbon|null $started_at
 * @property Carbon|null $finished_at
 * @property Carbon $created_at
 * @property Carbon $updated_at
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
        'affected_stock_ids',
        'started_at',
        'finished_at',
    ];

    protected function casts(): array
    {
        return [
            'type' => ImportType::class,
            'status' => ImportState::class,
            'total_rows' => 'integer',
            'processed_rows' => 'integer',
            'created_rows' => 'integer',
            'updated_rows' => 'integer',
            'failed_rows' => 'integer',
            'errors' => 'array',
            'affected_stock_ids' => 'array',
            'started_at' => 'datetime',
            'finished_at' => 'datetime',
        ];
    }
}
