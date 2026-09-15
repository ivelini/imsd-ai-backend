<?php

namespace App\Filament\Clusters\Settings\Resources\Admins;

use App\Filament\Clusters\Settings\Resources\Admins\Pages\CreateAdmin;
use App\Filament\Clusters\Settings\Resources\Admins\Pages\EditAdmin;
use App\Filament\Clusters\Settings\Resources\Admins\Pages\ListAdmins;
use App\Filament\Clusters\Settings\Resources\Admins\Schemas\AdminForm;
use App\Filament\Clusters\Settings\Resources\Admins\Tables\AdminsTable;
use App\Filament\Clusters\Settings\SettingsCluster;
use App\Models\Auth\Admin;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;

class AdminResource extends Resource
{
    protected static ?string $cluster = SettingsCluster::class;

    protected static ?string $model = Admin::class;

    protected static ?string $navigationLabel = 'Администраторы';

    protected static ?string $modelLabel = 'Администратор';

    protected static ?string $pluralModelLabel = 'Администраторы';

    protected static bool $hasTitleCaseModelLabel = false;

    public static function form(Schema $schema): Schema
    {
        return AdminForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return AdminsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListAdmins::route('/'),
            'create' => CreateAdmin::route('/create'),
            'edit' => EditAdmin::route('/{record}/edit'),
        ];
    }
}
