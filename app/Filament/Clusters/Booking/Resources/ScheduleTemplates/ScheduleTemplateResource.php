<?php

namespace App\Filament\Clusters\Booking\Resources\ScheduleTemplates;

use App\Filament\Clusters\Booking\BookingCluster;
use App\Filament\Clusters\Booking\BookingGroupEnum;
use App\Filament\Clusters\Booking\Resources\ScheduleTemplates\Pages\CreateScheduleTemplate;
use App\Filament\Clusters\Booking\Resources\ScheduleTemplates\Pages\EditScheduleTemplate;
use App\Filament\Clusters\Booking\Resources\ScheduleTemplates\Pages\ListScheduleTemplates;
use App\Filament\Clusters\Booking\Resources\ScheduleTemplates\Schemas\ScheduleTemplateForm;
use App\Filament\Clusters\Booking\Resources\ScheduleTemplates\Tables\ScheduleTemplatesTable;
use App\Models\Booking\ScheduleTemplate;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class ScheduleTemplateResource extends Resource
{
    protected static ?string $cluster = BookingCluster::class;

    protected static string|\UnitEnum|null $navigationGroup = BookingGroupEnum::Settings->value;

    protected static ?string $model = ScheduleTemplate::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClock;

    protected static ?string $navigationLabel = 'Расписание недели';

    public static function form(Schema $schema): Schema
    {
        return ScheduleTemplateForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ScheduleTemplatesTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListScheduleTemplates::route('/'),
            'create' => CreateScheduleTemplate::route('/create'),
            'edit' => EditScheduleTemplate::route('/{record}/edit'),
        ];
    }
}
