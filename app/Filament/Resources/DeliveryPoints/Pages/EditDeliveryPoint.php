<?php

namespace App\Filament\Resources\DeliveryPoints\Pages;

use App\Filament\Resources\DeliveryPoints\DeliveryPointResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditDeliveryPoint extends EditRecord
{
    protected static string $resource = DeliveryPointResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
