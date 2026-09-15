<?php

namespace App\Filament\Clusters\Booking\Resources\Slots\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class SlotForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                // Дата и час — только при создании: на правке слот уже привязан к записям, а свои дата и час он несёт в заголовке
                DatePicker::make('date')
                    ->label('Дата')
                    ->required()
                    ->visibleOn('create'),
                Select::make('hour')
                    ->label('Час')
                    ->options(collect(range(0, 23))->mapWithKeys(fn (int $hour): array => [$hour => sprintf('%02d:00', $hour)]))
                    ->required()
                    ->visibleOn('create'),
                Toggle::make('is_closed')
                    ->label('Слот закрыт')
                    ->default(false),
            ]);
    }
}
