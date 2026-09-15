<?php

namespace App\Preconditions\Booking;

use App\Models\Booking\BookingService;
use DomainException;

/**
 * Проверка: услугу не используют прайс-правила, строки записей и комплексы перед удалением.
 * У этих связей FK restrictOnDelete — удаление привязанной услуги падало бы ошибкой запроса.
 */
final readonly class EnsureBookingServiceIsUnused
{
    public function ensure(BookingService $service): void
    {
        // Счётчики читает сама проверка: забытый withCount у вызывающего молча выключил бы запрет
        $service->loadCount(['priceRules', 'bookingItems', 'complexes']);

        $links = [];

        if (($service->price_rules_count ?? 0) > 0) {
            $links[] = "прайс-правила ({$service->price_rules_count})";
        }

        if (($service->booking_items_count ?? 0) > 0) {
            $links[] = "строки записей ({$service->booking_items_count})";
        }

        if (($service->complexes_count ?? 0) > 0) {
            $links[] = "комплексы ({$service->complexes_count})";
        }

        if ($links === []) {
            return;
        }

        throw new DomainException(
            "Невозможно удалить услугу «{$service->name}»: привязаны ".implode(', ', $links)
            .'. Услугу деактивируют (поле «Активна»), а не удаляют.'
        );
    }
}
