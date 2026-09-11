<?php

namespace App\Models\Booking;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

/**
 * Шаблон недели: для каждого дня часы работы (0 (пн) – 6 (вс)); open+close = null → выходной.
 *
 * @property int $id
 * @property int $weekday
 * @property string|null $open_time
 * @property string|null $close_time
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class ScheduleTemplate extends Model
{
    protected $table = 'booking_schedule_templates';

    protected $fillable = [
        'weekday',
        'open_time',
        'close_time',
    ];
}
