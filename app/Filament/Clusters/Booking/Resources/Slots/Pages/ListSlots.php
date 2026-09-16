<?php

namespace App\Filament\Clusters\Booking\Resources\Slots\Pages;

use App\Filament\Clusters\Booking\Resources\Slots\SlotResource;
use App\Filament\Support\PeriodFilter;
use Filament\Resources\Pages\ListRecords;

class ListSlots extends ListRecords
{
    protected static string $resource = SlotResource::class;

    /** Стартовый вид — слоты текущей недели: оператору нужна неделя целиком, а не один день. */
    public function mount(): void
    {
        parent::mount();

        $this->tableFilters['period'] ??= PeriodFilter::weekDefaults();
    }
}
