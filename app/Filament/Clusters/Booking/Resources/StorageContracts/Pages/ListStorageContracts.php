<?php

namespace App\Filament\Clusters\Booking\Resources\StorageContracts\Pages;

use App\Filament\Clusters\Booking\Resources\StorageContracts\StorageContractResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListStorageContracts extends ListRecords
{
    protected static string $resource = StorageContractResource::class;

    /** @return array<int, CreateAction> */
    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
