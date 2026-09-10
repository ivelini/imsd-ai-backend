<?php

namespace App\Filament\Clusters\Catalog\Resources\WarehouseMarkupRules\Pages;

use App\Filament\Clusters\Catalog\Resources\WarehouseMarkupRules\WarehouseMarkupRuleResource;
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
