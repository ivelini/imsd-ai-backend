<?php

namespace App\Models\Booking;

use App\Casts\MoneyCast;
use App\Enums\Booking\BookingSource;
use App\Enums\Booking\BookingStatus;
use App\Enums\Booking\CarType;
use App\Models\Auth\Admin;
use App\Models\User;
use App\ValueObjects\Money;
use Carbon\Carbon;
use Database\Factories\Booking\BookingFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * Запись на конкретное время начала; параметры авто и цена — снимок на момент создания (ADR 0004 tireslot).
 *
 * @property int $id
 * @property int $user_id
 * @property int $slot_id
 * @property int|null $booking_code_id заявка сайта, подтвердившая запись (верификатор отмены)
 * @property string $start_time
 * @property BookingStatus $status
 * @property BookingSource $source
 * @property string|null $cancel_reason
 * @property string|null $idempotency_key
 * @property int $radius
 * @property CarType $car_type
 * @property string|null $plate госномер из заявки (снимок)
 * @property Money $total_price
 * @property int|null $operator_id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class Booking extends Model
{
    /** @use HasFactory<BookingFactory> */
    use HasFactory;

    protected $table = 'bookings';

    protected $fillable = [
        'user_id', 'slot_id', 'booking_code_id', 'start_time', 'status', 'source',
        'cancel_reason', 'idempotency_key',
        'radius', 'car_type', 'plate', 'total_price', 'operator_id',
    ];

    protected function casts(): array
    {
        return [
            'status' => BookingStatus::class,
            'source' => BookingSource::class,
            'car_type' => CarType::class,
            'total_price' => MoneyCast::class,
        ];
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return BelongsTo<Slot, $this> */
    public function slot(): BelongsTo
    {
        return $this->belongsTo(Slot::class);
    }

    /** @return BelongsTo<BookingCode, $this> код, подтвердивший заявку (nullable — запись по звонку) */
    public function bookingCode(): BelongsTo
    {
        return $this->belongsTo(BookingCode::class);
    }

    /** @return BelongsTo<Admin, $this> сотрудник панели, создавший запись (nullable) */
    public function operator(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'operator_id');
    }

    /** @return HasMany<BookingItem, $this> */
    public function items(): HasMany
    {
        return $this->hasMany(BookingItem::class);
    }

    /** @return HasOne<Slot, $this> слот, закрытый привязкой к этой записи */
    public function closedSlot(): HasOne
    {
        return $this->hasOne(Slot::class, 'booking_id');
    }

    /** Запись, созданная этим кодом — для повторного submit уже использованного кода. */
    public static function forCode(BookingCode $code): ?Booking
    {
        return static::query()->where('booking_code_id', $code->id)->first();
    }
}
