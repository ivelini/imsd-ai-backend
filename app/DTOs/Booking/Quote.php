<?php

namespace App\DTOs\Booking;

/** Результат расчёта цены набора услуг: строки и итог в копейках. */
final readonly class Quote
{
    /**
     * @param  list<QuoteLine>  $lines
     */
    public function __construct(
        public array $lines,
        public int $total,
    ) {}
}
