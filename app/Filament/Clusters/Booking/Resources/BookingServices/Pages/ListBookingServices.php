<?php

namespace App\Filament\Clusters\Booking\Resources\BookingServices\Pages;

use App\Filament\Clusters\Booking\Resources\BookingServices\BookingServiceResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListBookingServices extends ListRecords
{
    protected static string $resource = BookingServiceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
