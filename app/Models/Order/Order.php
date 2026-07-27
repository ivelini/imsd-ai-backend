<?php

namespace App\Models\Order;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Заказ клиента. */
class Order extends Model
{
    protected $fillable = [
        'user_id',
        'status',
        'total',
        'delivery_type',
        'payment_method',
        'contact_info',
    ];

    protected function casts(): array
    {
        return [
            'total' => 'decimal:2',
            'contact_info' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }
}
