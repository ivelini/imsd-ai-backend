<?php

namespace App\Filament\Resources\DeliverySchedules\Pages;

use App\Filament\Resources\DeliverySchedules\DeliveryScheduleResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditDeliverySchedule extends EditRecord
{
    protected static string $resource = DeliveryScheduleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
