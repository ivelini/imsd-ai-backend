<?php

namespace App\Filament\Clusters\Booking\Resources\StorageContracts\Pages;

use App\Filament\Clusters\Booking\Resources\StorageContracts\StorageContractResource;
use App\Filament\Concerns\SavesAndCloses;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditStorageContract extends EditRecord
{
    use SavesAndCloses;

    protected static string $resource = StorageContractResource::class;

    /** @return array<int, DeleteAction> */
    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
