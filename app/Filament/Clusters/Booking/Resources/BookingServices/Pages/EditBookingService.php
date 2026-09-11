<?php

namespace App\Filament\Clusters\Booking\Resources\BookingServices\Pages;

use App\Filament\Clusters\Booking\Resources\BookingServices\BookingServiceResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditBookingService extends EditRecord
{
    protected static string $resource = BookingServiceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
