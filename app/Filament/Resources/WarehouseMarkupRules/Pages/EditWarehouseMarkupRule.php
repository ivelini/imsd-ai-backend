<?php

namespace App\Filament\Resources\WarehouseMarkupRules\Pages;

use App\Filament\Resources\WarehouseMarkupRules\WarehouseMarkupRuleResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditWarehouseMarkupRule extends EditRecord
{
    protected static string $resource = WarehouseMarkupRuleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
