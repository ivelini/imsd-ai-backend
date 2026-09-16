<?php

namespace App\Filament\Clusters\Booking\Resources\Bookings\Pages;

use App\Filament\Clusters\Booking\Resources\Bookings\BookingResource;
use App\Filament\Support\PeriodFilter;
use Filament\Resources\Pages\ListRecords;

class ListBookings extends ListRecords
{
    protected static string $resource = BookingResource::class;

    /** Стартовый вид — записи текущей недели; тот же приём, что в списке слотов. */
    public function mount(): void
    {
        parent::mount();

        $this->tableFilters['period'] ??= PeriodFilter::weekDefaults();
    }
}
