<?php

namespace App\Filament\Resources\CityPriceRules\Pages;

use App\Filament\Resources\CityPriceRules\CityPriceRuleResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditCityPriceRule extends EditRecord
{
    protected static string $resource = CityPriceRuleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
