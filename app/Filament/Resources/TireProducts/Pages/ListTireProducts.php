<?php

namespace App\Filament\Resources\TireProducts\Pages;

use App\Filament\Resources\TireProducts\TireProductResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListTireProducts extends ListRecords
{
    protected static string $resource = TireProductResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
