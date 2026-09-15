<?php

namespace App\Filament\Clusters\Booking\Resources\PriceRules\Schemas;

use App\Enums\Booking\CarType;
use App\Enums\Booking\WheelRadius;
use App\ValueObjects\Money;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Illuminate\Validation\Rules\Unique;

class PriceRuleForm
{
    /**
     * @param  int|null  $serviceId  услуга-владелец: в карточке услуги её поля в форме нет, в разделе
     *                               «Прайс-правила» — null, услуга выбирается полем
     */
    public static function configure(Schema $schema, ?int $serviceId = null): Schema
    {
        // В карточке услуги поля «Услуга» нет — услуга известна из владельца страницы
        $serviceField = $serviceId === null
            ? [Select::make('service_id')
                ->label('Услуга')
                ->relationship('service', 'name', fn ($query) => $query->where('is_active', true))
                ->required()]
            : [];

        return $schema
            ->components([
                ...$serviceField,
                // Значения int, а не enum: колонка radius без каста (как и в БД).
                // Уникальность куба (услуга × радиус × тип) вешается на радиус: в карточке услуги
                // поля «Услуга» нет, и она берётся из владельца страницы
                Select::make('radius')
                    ->label('Радиус')
                    ->options(WheelRadius::options())
                    ->required()
                    ->live()
                    ->unique(
                        table: 'booking_price_rules',
                        column: 'radius',
                        ignoreRecord: true,
                        modifyRuleUsing: fn (Unique $rule, Get $get): Unique => $rule
                            ->where('service_id', $serviceId ?? (int) $get('service_id'))
                            ->where('car_type', self::carTypeValue($get('car_type'))),
                    )
                    ->validationMessages([
                        'unique' => $serviceId === null
                            ? 'Правило для такой комбинации услуги, радиуса и типа уже есть'
                            : 'Правило для такой комбинации радиуса и типа уже есть',
                    ]),
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

    /** Значение типа авто для условия уникальности: в форме может быть и enum, и строка. */
    private static function carTypeValue(mixed $carType): string
    {
        return $carType instanceof CarType ? $carType->value : (string) $carType;
    }
}
