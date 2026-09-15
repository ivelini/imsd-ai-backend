<?php

namespace App\Filament\Clusters\Booking\Resources\BookingServices\RelationManagers;

use App\Filament\Clusters\Booking\Resources\PriceRules\Schemas\PriceRuleForm;
use App\Filament\Clusters\Booking\Resources\PriceRules\Tables\PriceRulesTable;
use Filament\Actions\CreateAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Table;

/**
 * Прайс-правила услуги в её карточке: тот же куб цен, что в разделе «Прайс-правила», но в пределах услуги —
 * форма и таблица общие, услуга берётся из владельца страницы.
 */
class PriceRulesRelationManager extends RelationManager
{
    protected static string $relationship = 'priceRules';

    protected static ?string $title = 'Прайс-правила';

    public function form(Schema $schema): Schema
    {
        return PriceRuleForm::configure($schema, serviceId: (int) $this->getOwnerRecord()->getKey());
    }

    public function table(Table $table): Table
    {
        return PriceRulesTable::configure($table, withServiceColumn: false)
            ->defaultSort('radius')
            ->headerActions([
                CreateAction::make()->label('Добавить правило'),
            ]);
    }
}
