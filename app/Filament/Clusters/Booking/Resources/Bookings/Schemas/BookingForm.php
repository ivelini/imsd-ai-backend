<?php

namespace App\Filament\Clusters\Booking\Resources\Bookings\Schemas;

use App\Enums\Booking\BookingStatus;
use App\Enums\Booking\CarType;
use App\Enums\Booking\WheelRadius;
use App\Models\Booking\BookingService;
use App\Models\Booking\Slot;
use App\Services\Booking\PriceCalculator;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

/**
 * Одна схема на create/edit с видимостью по операции (operation()-колбэков в этой
 * версии Filament нет): создание — клиент, слот и состав; правка — статус и снимок.
 */
class BookingForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('phone')
                    ->label('Телефон')
                    ->required()
                    ->maxLength(30)
                    ->visibleOn('create'),
                TextInput::make('name')
                    ->label('Имя клиента')
                    ->required()
                    ->maxLength(255)
                    ->visibleOn('create'),
                TextInput::make('plate')
                    ->label('Госномер')
                    ->maxLength(20),
                Select::make('slot_id')
                    ->label('Слот')
                    ->options(
                        Slot::query()
                            ->whereDate('date', '>=', now()->toDateString())
                            ->orderBy('date')
                            ->orderBy('hour')
                            ->get()
                            ->mapWithKeys(fn (Slot $slot): array => [
                                $slot->id => sprintf('%s %02d:00%s', $slot->date->format('d.m.Y'), $slot->hour, $slot->is_closed ? ' (закрыт)' : ''),
                            ])
                    )
                    ->required()
                    ->searchable()
                    ->visibleOn('create'),
                Select::make('radius')
                    ->label('Радиус')
                    ->options(WheelRadius::options())
                    ->required(),
                Select::make('car_type')
                    ->label('Тип авто')
                    ->options(CarType::bookableOptions())
                    ->required(),
                // Состав — только при создании: цена строки пересчитывается сервером (снимок)
                Repeater::make('composition')
                    ->label('Состав')
                    ->schema([
                        Select::make('service_id')
                            ->label('Услуга')
                            ->options(
                                BookingService::query()
                                    ->where('is_active', true)
                                    ->orderBy('id')
                                    ->pluck('name', 'id')
                            )
                            ->required()
                            ->distinct(),
                        Select::make('quantity')
                            ->label('Количество')
                            ->options(collect(range(PriceCalculator::MIN_QUANTITY, PriceCalculator::MAX_QUANTITY))
                                ->mapWithKeys(fn (int $quantity): array => [$quantity => (string) $quantity]))
                            ->default(PriceCalculator::DEFAULT_QUANTITY)
                            ->required(),
                    ])
                    ->columns(2)
                    ->minItems(1)
                    ->required()
                    ->visibleOn('create'),
                Toggle::make('close_slot')
                    ->label('Закрыть слот с привязкой к записи')
                    ->default(true)
                    ->visibleOn('create'),
                Select::make('status')
                    ->label('Статус')
                    ->options(
                        collect(BookingStatus::cases())
                            ->mapWithKeys(fn (BookingStatus $status): array => [$status->value => $status->label()])
                    )
                    ->required()
                    ->visibleOn('edit'),
                TextInput::make('cancel_reason')
                    ->label('Причина отмены')
                    ->maxLength(255)
                    ->visibleOn('edit'),
            ]);
    }
}
