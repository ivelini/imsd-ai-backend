<?php

namespace App\DTOs\Catalog;

/** Результат расчёта доставки для карточки товара. */
final readonly class DeliveryInfo
{
    /**
     * @param  array{delivery_days: int|null, markup: float|null}  $delivery
     * @param  array<int, int|null>  $stockDays  stock_id → delivery_days
     */
    public function __construct(
        public array $delivery,
        public array $stockDays,
    ) {}
}
