<?php

namespace App\Enums\Catalog;

/** Статус заказа: от нового до возврата. */
enum OrderState: string
{
    case Pending = 'pending';
    case Paid = 'paid';
    case Processing = 'processing';
    case Shipped = 'shipped';
    case Delivered = 'delivered';
    case Cancelled = 'cancelled';
    case Refunded = 'refunded';
}
