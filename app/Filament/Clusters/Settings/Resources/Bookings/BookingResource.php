<?php

namespace App\Filament\Clusters\Settings\Resources\Bookings;

use App\Filament\Clusters\Settings\Resources\Bookings\Pages\CreateSetting;
use App\Filament\Clusters\Settings\Resources\Bookings\Pages\EditSetting;
use App\Filament\Clusters\Settings\Resources\Bookings\Pages\ListSettings;
use App\Filament\Clusters\Settings\Resources\Bookings\Schemas\SettingForm;
use App\Filament\Clusters\Settings\Resources\Bookings\Tables\SettingsTable;
use App\Filament\Clusters\Settings\SettingsCluster;
use App\Models\System\Setting;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;

class BookingResource extends Resource
{
    protected static ?string $cluster = SettingsCluster::class;

    protected static ?string $model = Setting::class;

    protected static ?string $navigationLabel = 'Настройки';

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
