<?php

namespace App\Filament\Clusters\Catalog\Resources\DeliverySchedules\Pages;

use App\Filament\Clusters\Catalog\Resources\DeliverySchedules\DeliveryScheduleResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListDeliverySchedules extends ListRecords
{
    protected static string $resource = DeliveryScheduleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
