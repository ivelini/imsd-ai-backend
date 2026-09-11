<?php

namespace App\DTOs\Booking;

use App\ValueObjects\Money;

/** Результат расчёта цены набора услуг: строки и итог. */
final readonly class Quote
{
    /**
     * @param  list<QuoteLine>  $lines
     */
    public function __construct(
        public array $lines,
        public Money $total,
    ) {}
}
