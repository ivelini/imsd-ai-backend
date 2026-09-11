<?php

namespace App\Filament\Clusters\Booking\Resources\ScheduleTemplates\Schemas;

use App\Enums\Common\WeekDay;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TimePicker;
use Filament\Schemas\Schema;

class ScheduleTemplateForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('weekday')
                    ->label('День недели')
                    ->options(
                        collect(WeekDay::cases())
                            ->mapWithKeys(fn (WeekDay $day): array => [$day->value => $day->label()])
                    )
                    ->required()
                    ->unique(ignoreRecord: true),
                // Пара open + close = null → выходной день
                TimePicker::make('open_time')
                    ->label('Открытие')
                    ->seconds(false)
                    ->nullable(),
                TimePicker::make('close_time')
                    ->label('Закрытие')
                    ->seconds(false)
                    ->nullable(),
            ]);
    }
}
