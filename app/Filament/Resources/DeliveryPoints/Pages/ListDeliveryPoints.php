<?php

namespace App\Filament\Resources\DeliveryPoints\Pages;

use App\Filament\Resources\DeliveryPoints\DeliveryPointResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListDeliveryPoints extends ListRecords
{
    protected static string $resource = DeliveryPointResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
