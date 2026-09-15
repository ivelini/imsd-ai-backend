<?php

namespace App\Filament\Clusters\Booking\Resources\BookingServices;

use App\Filament\Clusters\Booking\BookingCluster;
use App\Filament\Clusters\Booking\BookingGroupEnum;
use App\Filament\Clusters\Booking\Resources\BookingServices\Pages\CreateBookingService;
use App\Filament\Clusters\Booking\Resources\BookingServices\Pages\EditBookingService;
use App\Filament\Clusters\Booking\Resources\BookingServices\Pages\ListBookingServices;
use App\Filament\Clusters\Booking\Resources\BookingServices\RelationManagers\PriceRulesRelationManager;
use App\Filament\Clusters\Booking\Resources\BookingServices\Schemas\BookingServiceForm;
use App\Filament\Clusters\Booking\Resources\BookingServices\Tables\BookingServicesTable;
use App\Filament\Support\PanelAction;
use App\Models\Booking\BookingService;
use App\Preconditions\Booking\EnsureBookingServiceIsUnused;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class BookingServiceResource extends Resource
{
    protected static ?string $cluster = BookingCluster::class;

    protected static string|\UnitEnum|null $navigationGroup = BookingGroupEnum::Services->value;

    protected static ?int $navigationSort = 20;

    protected static ?string $model = BookingService::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedWrenchScrewdriver;

    protected static ?string $navigationLabel = 'Услуги';

    protected static ?string $modelLabel = 'Услуга';

    protected static ?string $pluralModelLabel = 'Услуги';

    protected static bool $hasTitleCaseModelLabel = false;

    public static function form(Schema $schema): Schema
    {
        return BookingServiceForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return BookingServicesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            PriceRulesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListBookingServices::route('/'),
            'create' => CreateBookingService::route('/create'),
            'edit' => EditBookingService::route('/{record}/edit'),
        ];
    }

    /** Удаление услуги под проверкой привязок — один путь для листинга и карточки. */
    public static function deleteAction(): DeleteAction
    {
        return DeleteAction::make()
            ->action(function (BookingService $record, EnsureBookingServiceIsUnused $ensure): void {
                PanelAction::run('Услуга удалена', function () use ($record, $ensure): void {
                    $ensure->ensure($record);
                    $record->delete();
                });
            });
    }
}
