<?php

namespace App\Filament\Resources\CityPriceRules\Pages;

use App\Filament\Resources\CityPriceRules\CityPriceRuleResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListCityPriceRules extends ListRecords
{
    protected static string $resource = CityPriceRuleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
