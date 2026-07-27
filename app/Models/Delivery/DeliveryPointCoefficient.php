<?php

namespace App\Models\Delivery;

use Illuminate\Database\Eloquent\Model;

/** Коэффициент доставки: цена от/до, тип товара → множитель. */
class DeliveryPointCoefficient extends Model
{
    protected $fillable = [
        'price_from',
        'price_to',
        'product_type',
        'coefficient',
    ];
}
