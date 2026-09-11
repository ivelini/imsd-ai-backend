<?php

namespace App\Filament\Clusters\Booking\Resources\BookingServices\Schemas;

use App\Enums\Booking\ServiceCategory;
use App\ValueObjects\Money;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class BookingServiceForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('Название')
                    ->required()
                    ->maxLength(255)
                    ->unique(ignoreRecord: true),
                Select::make('category')
                    ->label('Категория')
                    ->options(ServiceCategory::class)
                    ->default(ServiceCategory::Tire)
                    ->required(),
                Toggle::make('is_active')
                    ->label('Активна')
                    ->default(true),
                // Рубли в форме, копейки в БД (решение №10 плана переноса)
                TextInput::make('base_price')
                    ->label('Цена, ₽')
                    ->required()
                    ->rules(['numeric', 'min:0'])
                    ->formatStateUsing(fn (?Money $state): string => $state === null ? '0' : (string) $state->toRubles())
                    ->dehydrateStateUsing(fn (string $state): Money => Money::fromRubles($state)),
            ]);
    }
}
