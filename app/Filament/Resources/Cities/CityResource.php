<?php

namespace App\Filament\Resources\Cities;

use App\Filament\Resources\Cities\Pages\ListCities;
use App\Filament\Resources\Cities\Tables\CitiesTable;
use App\Models\Delivery\City;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

/** Города — read-only справочник (наполняется импортом гео-точек). */
class CityResource extends Resource
{
    protected static ?string $model = City::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedMap;

    protected static ?string $navigationLabel = 'Города';

    protected static ?string $modelLabel = 'город';

    protected static ?string $pluralModelLabel = 'Города';

    public static function table(Table $table): Table
    {
        return CitiesTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCities::route('/'),
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit(Model $record): bool
    {
        return false;
    }

    public static function canDelete(Model $record): bool
    {
        return false;
    }
}
