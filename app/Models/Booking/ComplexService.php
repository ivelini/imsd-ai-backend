<?php

namespace App\Models\Booking;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * Готовый комплекс услуг (например, «Сезонный шиномонтаж»): набор без собственной цены.
 * На сайте клик по комплексу отмечает входящие услуги с количеством 4; в запись комплекс
 * не попадает — хранятся только услуги с количествами.
 *
 * @property int $id
 * @property string $name
 * @property bool $is_active
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class ComplexService extends Model
{
    protected $table = 'booking_complex_services';

    protected $fillable = [
        'name',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    /** @return BelongsToMany<BookingService, $this> */
    public function services(): BelongsToMany
    {
        return $this->belongsToMany(BookingService::class, 'booking_complex_service_items', 'complex_service_id', 'service_id');
    }
}
