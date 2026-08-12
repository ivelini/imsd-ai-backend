<?php

namespace App\Models\Delivery;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Точка выдачи — физический адрес, где клиент забирает товар.
 *
 * @property int $id
 * @property int $city_id
 * @property string $address
 * @property string|null $phone
 * @property string|null $email
 * @property string|null $work_hours
 * @property string|null $info
 * @property bool $pickup_from_truck
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property-read City $city
 */
class DeliveryPoint extends Model
{
    protected $fillable = [
        'city_id',
        'address',
        'phone',
        'email',
        'work_hours',
        'info',
        'pickup_from_truck',
    ];

    protected function casts(): array
    {
        return [
            'pickup_from_truck' => 'boolean',
        ];
    }

    public function city(): BelongsTo
    {
        return $this->belongsTo(City::class);
    }
}
