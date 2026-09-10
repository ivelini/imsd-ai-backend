<?php

namespace App\Filament\Resources\WheelProducts\Pages;

use App\Filament\Resources\WheelProducts\WheelProductResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListWheelProducts extends ListRecords
{
    protected static string $resource = WheelProductResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
