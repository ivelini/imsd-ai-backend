<?php

namespace App\Services\Booking;

use App\DTOs\Booking\Quote;
use App\DTOs\Booking\QuoteLine;
use App\Enums\Booking\CarType;
use App\Models\Booking\BookingService;
use App\Models\Booking\PriceRule;
use App\ValueObjects\Money;
use DomainException;
use Illuminate\Support\Collection;
use InvalidArgumentException;

/**
 * Расчёт цены набора услуг по прайс-правилам (ФТ-2, ФТ-5).
 *
 * Единый источник для всех каналов: показ итога на сайте и серверный пересчёт
 * при подтверждении кода. Прайс-правило — цена за единицу (1 колесо/шт) для
 * комбинации (услуга, радиус, тип); строка итога = цена × количество.
 * Подбор — только точным совпадением:
 * - у услуги нет правил вовсе — цена не зависит от параметров → base_price;
 * - правила есть, комбинации нет — DomainException 422 (потерянное правило —
 *   баг данных; молчаливая база дала бы неверную цену).
 */
class PriceCalculator
{
    /** Границы количества услуги (колёс/шт) — единственный источник правила 1–4. */
    public const MIN_QUANTITY = 1;

    public const MAX_QUANTITY = 4;

    /** Сезонный комплект: дефолт количества при выборе с сайта (ФТ-4). */
    public const DEFAULT_QUANTITY = 4;

    /**
     * @param  Collection<int, BookingService>  $services
     * @param  array<int, int>  $quantities  service_id => количество MIN–MAX_QUANTITY
     */
    public function calculate(Collection $services, int $radius, CarType $carType, array $quantities): Quote
    {
        $rulesByService = $this->rulesByService($services);

        $lines = [];
        $total = Money::fromKopecks(0);
        foreach ($services as $service) {
            $quantity = $this->quantityFor($service->id, $quantities);
            $unitPrice = $this->priceFor($service, $rulesByService[$service->id] ?? [], $radius, $carType);
            $linePrice = $unitPrice->multiply($quantity);

            $lines[] = new QuoteLine($service, $unitPrice, $quantity, $linePrice);
            $total = $total->add($linePrice);
        }

        return new Quote($lines, $total);
    }

    /**
     * @param  array<int, int>  $quantities
     */
    private function quantityFor(int $serviceId, array $quantities): int
    {
        $quantity = $quantities[$serviceId] ?? self::MIN_QUANTITY;
        if ($quantity < self::MIN_QUANTITY || $quantity > self::MAX_QUANTITY) {
            throw new InvalidArgumentException("Количество услуги {$serviceId} вне границ ".self::MIN_QUANTITY.'–'.self::MAX_QUANTITY);
        }

        return $quantity;
    }

    /**
     * @param  Collection<int, BookingService>  $services
     * @return array<int, list<PriceRule>> service_id => правила
     */
    private function rulesByService(Collection $services): array
    {
        return PriceRule::query()
            ->whereIn('service_id', $services->pluck('id'))
            ->get()
            ->groupBy('service_id')
            ->map(fn ($rules) => $rules->all())
            ->all();
    }

    /**
     * @param  list<PriceRule>  $rules
     */
    private function priceFor(BookingService $service, array $rules, int $radius, CarType $carType): Money
    {
        if ($rules === []) {
            return $service->base_price;
        }

        foreach ($rules as $rule) {
            if ($rule->radius === $radius && $rule->car_type === $carType) {
                return $rule->price;
            }
        }

        throw new DomainException(sprintf(
            'Нет прайс-правила: услуга %s, R%d, %s',
            $service->name,
            $radius,
            $carType->label(),
        ), 422);
    }
}
