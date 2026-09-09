<?php

namespace App\Filament\Resources\WarehouseMarkupRules\Pages;

use App\Filament\Resources\WarehouseMarkupRules\WarehouseMarkupRuleResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListWarehouseMarkupRules extends ListRecords
{
    protected static string $resource = WarehouseMarkupRuleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
