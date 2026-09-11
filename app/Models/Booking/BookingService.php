<?php

namespace App\Models\Booking;

use App\Casts\MoneyCast;
use App\Enums\Booking\ServiceCategory;
use App\ValueObjects\Money;
use Carbon\Carbon;
use Database\Factories\Booking\BookingServiceFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Услуга шиномонтажа каталога (снятие/установка, балансировка…).
 *
 * @property int $id
 * @property string $name
 * @property ServiceCategory $category
 * @property bool $is_active
 * @property Money $base_price
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class BookingService extends Model
{
    /** @use HasFactory<BookingServiceFactory> */
    use HasFactory;

    protected $table = 'booking_services';

    protected $fillable = [
        'name',
        'category',
        'is_active',
        'base_price',
    ];

    protected function casts(): array
    {
        return [
            'category' => ServiceCategory::class,
            'is_active' => 'boolean',
            'base_price' => MoneyCast::class,
        ];
    }

    /** @return HasMany<PriceRule, $this> */
    public function priceRules(): HasMany
    {
        return $this->hasMany(PriceRule::class, 'service_id');
    }

    /** @return HasMany<BookingItem, $this> */
    public function bookingItems(): HasMany
    {
        return $this->hasMany(BookingItem::class, 'service_id');
    }

    /** @return BelongsToMany<ComplexService, $this> комплексы, в которые входит услуга */
    public function complexes(): BelongsToMany
    {
        return $this->belongsToMany(ComplexService::class, 'booking_complex_service_items', 'service_id', 'complex_service_id');
    }

    /** Активные услуги среди перечисленных id — нормализация устаревшего выбора шага. */
    public function scopeActiveByIds(Builder $query, array $ids): Builder
    {
        return $query->whereIn('id', $ids)->where('is_active', true);
    }
}
