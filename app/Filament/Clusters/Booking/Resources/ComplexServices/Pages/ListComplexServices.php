<?php

namespace App\Filament\Clusters\Booking\Resources\ComplexServices\Pages;

use App\Filament\Clusters\Booking\Resources\ComplexServices\ComplexServiceResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListComplexServices extends ListRecords
{
    protected static string $resource = ComplexServiceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
