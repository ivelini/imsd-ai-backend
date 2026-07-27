<?php

namespace App\Models\Delivery;

use App\Models\Catalog\Warehouse;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** График отгрузки со склада: день недели, время отсечки, сроки. */
class DeliverySchedule extends Model
{
    protected $fillable = [
        'warehouse_id',
        'day_of_week',
        'cutoff_time',
        'days_before',
        'days_after',
    ];

    protected function casts(): array
    {
        return [
            'cutoff_time' => 'string',
            'day_of_week' => 'integer',
            'days_before' => 'integer',
            'days_after' => 'integer',
        ];
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }
}
