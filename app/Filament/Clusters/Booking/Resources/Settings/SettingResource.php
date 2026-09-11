<?php

namespace App\Filament\Clusters\Booking\Resources\Settings;

use App\Filament\Clusters\Booking\BookingCluster;
use App\Filament\Clusters\Booking\BookingGroupEnum;
use App\Filament\Clusters\Booking\Resources\Settings\Pages\CreateSetting;
use App\Filament\Clusters\Booking\Resources\Settings\Pages\EditSetting;
use App\Filament\Clusters\Booking\Resources\Settings\Pages\ListSettings;
use App\Filament\Clusters\Booking\Resources\Settings\Schemas\SettingForm;
use App\Filament\Clusters\Booking\Resources\Settings\Tables\SettingsTable;
use App\Models\System\Setting;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class SettingResource extends Resource
{
    protected static ?string $cluster = BookingCluster::class;

    protected static string|\UnitEnum|null $navigationGroup = BookingGroupEnum::Settings->value;

    protected static ?string $model = Setting::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedAdjustmentsHorizontal;

    protected static ?string $navigationLabel = 'Настройки записи';

    public static function form(Schema $schema): Schema
    {
        return SettingForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return SettingsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListSettings::route('/'),
            'create' => CreateSetting::route('/create'),
            'edit' => EditSetting::route('/{record}/edit'),
        ];
    }
}
