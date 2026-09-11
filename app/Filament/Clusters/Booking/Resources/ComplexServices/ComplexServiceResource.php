<?php

namespace App\Filament\Clusters\Booking\Resources\ComplexServices;

use App\Filament\Clusters\Booking\BookingCluster;
use App\Filament\Clusters\Booking\BookingGroupEnum;
use App\Filament\Clusters\Booking\Resources\ComplexServices\Pages\CreateComplexService;
use App\Filament\Clusters\Booking\Resources\ComplexServices\Pages\EditComplexService;
use App\Filament\Clusters\Booking\Resources\ComplexServices\Pages\ListComplexServices;
use App\Filament\Clusters\Booking\Resources\ComplexServices\Schemas\ComplexServiceForm;
use App\Filament\Clusters\Booking\Resources\ComplexServices\Tables\ComplexServicesTable;
use App\Models\Booking\ComplexService;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class ComplexServiceResource extends Resource
{
    protected static ?string $cluster = BookingCluster::class;

    protected static string|\UnitEnum|null $navigationGroup = BookingGroupEnum::Services->value;

    protected static ?string $model = ComplexService::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedSquaresPlus;

    protected static ?string $navigationLabel = 'Комплексы услуг';

    public static function form(Schema $schema): Schema
    {
        return ComplexServiceForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ComplexServicesTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListComplexServices::route('/'),
            'create' => CreateComplexService::route('/create'),
            'edit' => EditComplexService::route('/{record}/edit'),
        ];
    }
}
