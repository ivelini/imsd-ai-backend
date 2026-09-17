<?php

namespace App\Filament\Clusters\Booking\Resources\StorageContracts\Pages;

use App\Filament\Clusters\Booking\Resources\StorageContracts\StorageContractResource;
use Filament\Resources\Pages\CreateRecord;

class CreateStorageContract extends CreateRecord
{
    protected static string $resource = StorageContractResource::class;

    /** Оператора проставляет панель — из формы поле не приходит. */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['operator_id'] = auth('admin')->id();

        return $data;
    }
}
