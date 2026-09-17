<?php

namespace App\Filament\Clusters\Booking\Resources\StorageContracts;

use App\Filament\Clusters\Booking\BookingCluster;
use App\Filament\Clusters\Booking\BookingGroupEnum;
use App\Filament\Clusters\Booking\Resources\StorageContracts\Pages\CreateStorageContract;
use App\Filament\Clusters\Booking\Resources\StorageContracts\Pages\EditStorageContract;
use App\Filament\Clusters\Booking\Resources\StorageContracts\Pages\ListStorageContracts;
use App\Filament\Clusters\Booking\Resources\StorageContracts\Schemas\StorageContractForm;
use App\Filament\Clusters\Booking\Resources\StorageContracts\Tables\StorageContractsTable;
use App\Models\Storage\StorageContract;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class StorageContractResource extends Resource
{
    protected static ?string $cluster = BookingCluster::class;

    protected static string|\UnitEnum|null $navigationGroup = BookingGroupEnum::Storage->value;

    protected static ?int $navigationSort = 30;

    protected static ?string $model = StorageContract::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedArchiveBox;

    protected static ?string $navigationLabel = 'Хранение';

    protected static ?string $modelLabel = 'Договор хранения';

    protected static ?string $pluralModelLabel = 'Договоры хранения';

    protected static bool $hasTitleCaseModelLabel = false;

    public static function form(Schema $schema): Schema
    {
        return StorageContractForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return StorageContractsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListStorageContracts::route('/'),
            'create' => CreateStorageContract::route('/create'),
            'edit' => EditStorageContract::route('/{record}/edit'),
        ];
    }
}
