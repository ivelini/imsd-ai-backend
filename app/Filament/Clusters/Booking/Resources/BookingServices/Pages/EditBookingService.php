<?php

namespace App\Filament\Clusters\Booking\Resources\BookingServices\Pages;

use App\Filament\Clusters\Booking\Resources\BookingServices\BookingServiceResource;
use App\Filament\Concerns\SavesAndCloses;
use Filament\Resources\Pages\EditRecord;

class EditBookingService extends EditRecord
{
    use SavesAndCloses;

    protected static string $resource = BookingServiceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            BookingServiceResource::deleteAction(),
        ];
    }
}
