<?php

namespace App\Filament\Clusters\Booking\Resources\PriceRules\Schemas;

use App\Enums\Booking\CarType;
use App\Enums\Booking\WheelRadius;
use App\ValueObjects\Money;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Illuminate\Validation\Rules\Unique;

class PriceRuleForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('service_id')
                    ->label('Услуга')
                    ->relationship('service', 'name', fn ($query) => $query->where('is_active', true))
                    ->required()
                    // Куб прайса: комбинация (услуга, радиус, тип) уникальна
                    ->unique(
                        table: 'booking_price_rules',
                        column: 'service_id',
                        ignoreRecord: true,
                        modifyRuleUsing: function (Unique $rule, $get): Unique {
                            $carType = $get('car_type');

                            return $rule
                                ->where('radius', (int) $get('radius'))
                                ->where('car_type', $carType instanceof CarType ? $carType->value : (string) $carType);
                        },
                    ),
                // Значения int, а не enum: колонка radius без каста (как и в БД)
                Select::make('radius')
                    ->label('Радиус')
                    ->options(WheelRadius::options())
                    ->required()
                    ->live(),
                Select::make('car_type')
                    ->label('Тип авто')
                    ->options(CarType::bookableOptions())
                    ->required()
                    ->live(),
                // Рубли в форме, копейки в БД (решение №10 плана переноса)
                TextInput::make('price')
                    ->label('Цена за единицу, ₽')
                    ->required()
                    ->rules(['numeric', 'min:0'])
                    ->formatStateUsing(fn (?Money $state): string => $state === null ? '0' : (string) $state->toRubles())
                    ->dehydrateStateUsing(fn (string $state): Money => Money::fromRubles($state)),
            ]);
    }
}
