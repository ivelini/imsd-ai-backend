<?php

namespace App\Actions\Booking;

use App\Models\Booking\BookingService;
use App\Models\Booking\ComplexService;

/**
 * Каталог шага услуг: активные услуги с флагом наличия прайс-правил
 * и комплексы с составом (id услуг).
 *
 * @return array{services: list<array{id: int, name: string, base_price: int, has_rules: bool}>, complexes: list<array{id: int, name: string, service_ids: list<int>}>}
 */
final readonly class GetBookingCatalog
{
    public function execute(): array
    {
        $services = BookingService::query()
            ->where('is_active', true)
            ->orderBy('id')
            ->withCount('priceRules')
            ->get(['id', 'name', 'base_price'])
            ->map(fn (BookingService $service): array => [
                'id' => $service->id,
                'name' => $service->name,
                'base_price' => $service->base_price->toKopecks(),
                'has_rules' => $service->price_rules_count > 0,
            ])
            ->all();

        $complexes = ComplexService::query()
            ->where('is_active', true)
            ->with(['services' => fn ($query) => $query->where('is_active', true)->orderBy('id')])
            ->orderBy('id')
            ->get()
            ->map(fn (ComplexService $complex): array => [
                'id' => $complex->id,
                'name' => $complex->name,
                'service_ids' => $complex->services->pluck('id')->all(),
            ])
            ->all();

        return ['services' => $services, 'complexes' => $complexes];
    }
}
