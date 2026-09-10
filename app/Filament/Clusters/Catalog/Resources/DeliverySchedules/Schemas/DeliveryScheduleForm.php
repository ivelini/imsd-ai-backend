<?php

namespace App\Filament\Clusters\Catalog\Resources\DeliverySchedules\Schemas;

use App\Enums\Common\WeekDay;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\TimePicker;
use Filament\Schemas\Schema;

class DeliveryScheduleForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('warehouse_id')
                    ->label('Склад')
                    ->relationship('warehouse', 'name')
                    ->required(),
                Select::make('day_of_week')
                    ->label('День недели')
                    ->options(collect(WeekDay::cases())->mapWithKeys(
                        fn (WeekDay $day): array => [$day->value => $day->label()]
                    ))
                    ->required(),
                TimePicker::make('cutoff_time')
                    ->label('Крайнее время заказа')
                    ->seconds(false)
                    ->required(),
                TextInput::make('days_before')
                    ->label('Дней до')
                    ->required()
                    ->numeric()
                    ->minValue(0),
                TextInput::make('days_after')
                    ->label('Дней после')
                    ->required()
                    ->numeric()
                    ->minValue(0),
            ]);
    }
}
