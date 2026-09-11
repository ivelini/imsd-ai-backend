<?php

namespace App\Preconditions\Booking;

use App\Enums\Booking\CarType;
use App\Models\Booking\BookingService;
use App\Services\Booking\PriceCalculator;
use Illuminate\Support\Collection;

/**
 * Проверка, что набор услуг рассчитывается прайсом: отсутствие комбинации —
 * DomainException 422 из PriceCalculator (потерянное правило — баг данных).
 * Расчёт здесь не используется — это валидация перед UX-показом цены.
 */
final readonly class EnsurePricingCombinationExists
{
    public function __construct(private PriceCalculator $priceCalculator) {}

    /**
     * @param  Collection<int, BookingService>  $services
     * @param  array<int, int>  $quantities
     */
    public function ensure(Collection $services, int $radius, CarType $carType, array $quantities): void
    {
        $this->priceCalculator->calculate($services, $radius, $carType, $quantities);
    }
}
