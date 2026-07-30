<?php

namespace App\Services\Catalog;

use App\Models\Catalog\Warehouse\Stock;
use App\Models\Catalog\Warehouse\Warehouse;
use App\Models\Delivery\CityDeliveryTime;
use App\Models\Delivery\CityPriceRule;
use App\Models\Delivery\DeliverySchedule;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/** Расчёт блока delivery для карточки товара (срок отгрузки + наценка города). */
final class DeliveryInfoService
{
    /** @var array<int, int> */
    private array $cityDeliveryDays = [];

    /** @var array<int, Collection<int, CityPriceRule>> */
    private array $cityPriceRules = [];

    /** Прикрепить delivery к одному товару. */
    public function enrichProduct(Model $product, ?int $cityId): void
    {
        if (! $product->relationLoaded('stocks')) {
            return;
        }

        $stocks = $product->getRelation('stocks');

        // Всегда считаем дни до отгрузки для каждого склада (не зависит от города)
        foreach ($stocks as $stock) {
            /** @var Stock $stock */
            /** @var Warehouse|null $wh */
            $wh = $stock->warehouse;
            $stock->deliveryDays = self::nextShipmentDays($wh?->deliverySchedules);
        }

        // Доставка до города — только если city_id передан
        if ($cityId === null) {
            return;
        }

        $info = $this->computeDelivery($stocks, $cityId);
        if ($info !== null) {
            $product->setRelation('delivery', $info);
        }
    }

    /** @return array{delivery_days: int, markup: float|null}|null */
    private function computeDelivery(Collection $stocks, int $cityId): ?array
    {
        $days = null;
        $cheapestPrice = null;

        // Используем уже вычисленные stock.deliveryDays (из enrichProduct)
        foreach ($stocks as $s) {
            /** @var Stock $s */
            if ($s->price === null) {
                continue;
            }

            if ($s->deliveryDays !== null) {
                $days = $days !== null ? min($days, $s->deliveryDays) : $s->deliveryDays;
            }

            $price = (float) $s->price;
            if ($cheapestPrice === null || $price < $cheapestPrice) {
                $cheapestPrice = $price;
            }
        }

        if ($days === null) {
            return null;
        }

        $cityDays = $this->loadCityDeliveryDays($cityId);
        if ($cityDays !== null) {
            $days += $cityDays;
        }

        $markup = $cheapestPrice !== null
            ? $this->findCityMarkup($cityId, $cheapestPrice)
            : null;

        return [
            'delivery_days' => $days,
            'markup' => $markup,
        ];
    }

    private function loadCityDeliveryDays(int $cityId): ?int
    {
        if (! isset($this->cityDeliveryDays[$cityId])) {
            $this->cityDeliveryDays[$cityId] = CityDeliveryTime::where('city_id', $cityId)
                ->value('delivery_days');
        }

        return $this->cityDeliveryDays[$cityId];
    }

    private function findCityMarkup(int $cityId, float $price): ?float
    {
        if (! isset($this->cityPriceRules[$cityId])) {
            $this->cityPriceRules[$cityId] = CityPriceRule::where('city_id', $cityId)->get();
        }

        $rule = $this->cityPriceRules[$cityId]->first(
            fn (CityPriceRule $r) => $price >= $r->price_from && $price <= $r->price_to
        );

        return $rule?->markup !== null ? (float) $rule->markup : null;
    }

    /** Расчёт ближайшего срока отгрузки со склада от текущего момента (без города). */
    public static function nextShipmentDays(?Collection $schedules): ?int
    {
        if ($schedules === null || $schedules->isEmpty()) {
            return null;
        }

        $now = Carbon::now();
        $todayDow = (int) $now->dayOfWeekIso - 1; // 0=Mon … 6=Sun
        $todayTime = $now->format('H:i');

        $byDay = $schedules->keyBy('day_of_week');

        $schedule = null;
        $offset = 0;

        for ($d = 0; $d <= 7; $d++) {
            $day = ($todayDow + $d) % 7;

            /** @var DeliverySchedule|null $schedule */
            $schedule = $byDay->get($day);
            if ($schedule !== null) {
                $offset = $d;
                break;
            }
        }

        if ($schedule === null) {
            return null;
        }

        $processing = ($offset === 0 && $todayTime >= $schedule->cutoff_time)
            ? $schedule->days_after
            : $schedule->days_before;

        return $processing + $offset;
    }
}
