<?php

namespace App\Filament\Clusters\Booking\Resources\Bookings\Schemas;

use App\Enums\Booking\BookingStatus;
use App\Enums\Booking\CarType;
use App\Enums\Booking\WheelRadius;
use App\Models\Booking\Booking;
use App\Models\Booking\BookingService;
use App\Models\Booking\Slot;
use App\Services\Booking\PriceCalculator;
use App\ValueObjects\Money;
use DomainException;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Repeater\TableColumn;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;

/**
 * Одна схема на create/edit с видимостью по операции (operation()-колбэков в этой
 * версии Filament нет): создание — клиент, слот и состав; правка — статус, снимок,
 * состав строками с ценой по прайсу и итоговая стоимость (ФТ-19).
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
                    ->live()
                    ->afterStateUpdated(fn (Get $get, Set $set) => self::fillStartTime($get, $set))
                    ->visibleOn('create'),
                // Время внутри часа слота: 14:00–14:59. Ровно на начало часа — час занимается целиком
                Select::make('start_time')
                    ->label('Время')
                    ->options(fn (?Booking $record, Get $get): array => self::timeOptions(self::slotHour($record, $get)))
                    ->required()
                    ->formatStateUsing(fn (?string $state): ?string => $state === null ? null : substr($state, 0, 5))
                    ->dehydrateStateUsing(fn (string $state): string => $state.':00'),
                // Радиус и тип авто — параметры прайса: на правке за ними следует цена строк состава
                Select::make('radius')
                    ->label('Радиус')
                    ->options(WheelRadius::options())
                    ->required()
                    ->live()
                    ->afterStateUpdated(fn (Get $get, Set $set) => self::repriceItems($get, $set)),
                Select::make('car_type')
                    ->label('Тип авто')
                    ->options(CarType::bookableOptions())
                    ->required()
                    ->live()
                    ->afterStateUpdated(fn (Get $get, Set $set) => self::repriceItems($get, $set)),
                Select::make('status')
                    ->label('Статус')
                    ->options(
                        collect(BookingStatus::cases())
                            ->mapWithKeys(fn (BookingStatus $status): array => [$status->value => $status->label()])
                    )
                    ->required()
                    ->visibleOn('edit'),
                // Состав — только при создании: цена строки пересчитывается сервером (снимок)
                Repeater::make('composition')
                    ->label('Состав')
                    ->schema([
                        self::serviceSelect('service_id'),
                        Select::make('quantity')
                            ->label('Количество')
                            ->options(self::quantityOptions())
                            ->default(PriceCalculator::DEFAULT_QUANTITY)
                            ->required(),
                    ])
                    ->columns(2)
                    ->minItems(1)
                    ->required()
                    ->visibleOn('create'),
                // Состав при правке: услуга подтягивается с готовой ценой по прайсу, цена правится оператором
                Repeater::make('items')
                    ->label('Услуги')
                    ->table([
                        TableColumn::make('Услуга'),
                        TableColumn::make('Цена, ₽'),
                        TableColumn::make('Количество'),
                    ])
                    ->schema([
                        self::serviceSelect('service_id')
                            ->live()
                            ->afterStateUpdated(fn (Get $get, Set $set) => self::fillItemPrice($get, $set)),
                        TextInput::make('price')
                            ->label('Цена, ₽')
                            ->required()
                            // rules вместо numeric(): NumberStateCast конфликтует с dehydration поля в Money
                            ->rules(['numeric', 'min:0'])
                            ->live(onBlur: true)
                            ->dehydrateStateUsing(fn (string $state): Money => Money::fromRubles($state)),
                        Select::make('quantity')
                            ->label('Количество')
                            ->options(self::quantityOptions())
                            ->required()
                            ->live(),
                    ])
                    ->minItems(1)
                    ->required()
                    ->visibleOn('edit'),
                // TextEntry вместо устаревшего Placeholder, но состояние — через state():
                // content() запись не рендерит (Placeholder::content как раз оборачивал state)
                TextEntry::make('items_total')
                    ->label('Итоговая стоимость')
                    ->state(fn (Get $get): string => self::itemsTotal($get('items')))
                    ->visibleOn('edit'),
                TextInput::make('cancel_reason')
                    ->label('Причина отмены')
                    ->maxLength(255)
                    ->visibleOn('edit'),
            ]);
    }

    /** @return array<string, string> время внутри часа слота: 14:00…14:59 */
    private static function timeOptions(?int $hour): array
    {
        if ($hour === null) {
            return [];
        }

        $options = [];
        foreach (range(0, 59) as $minute) {
            $time = sprintf('%02d:%02d', $hour, $minute);
            $options[$time] = $time;
        }

        return $options;
    }

    /** Час слота: на создании — выбранный слот, на правке — слот записи. */
    private static function slotHour(?Booking $record, Get $get): ?int
    {
        $slotId = $get('slot_id') ?? $record?->slot_id;
        $hour = is_numeric($slotId) ? Slot::query()->whereKey((int) $slotId)->value('hour') : null;

        return is_numeric($hour) ? (int) $hour : null;
    }

    /** Выбор слота подставляет его начало — оператор правит время, только если клиент приедет позже. */
    private static function fillStartTime(Get $get, Set $set): void
    {
        $hour = self::slotHour(null, $get);

        if ($hour !== null) {
            $set('start_time', sprintf('%02d:00', $hour));
        }
    }

    /** Выбор услуги: активные услуги каталога плюс уже прикреплённые к записи, повтор в составе запрещён. */
    private static function serviceSelect(string $name): Select
    {
        return Select::make($name)
            ->label('Услуга')
            ->options(fn (?Booking $record): array => self::serviceOptions($record))
            ->required()
            ->distinct();
    }

    /**
     * Filament валидирует значение Select по его опциям (`validation.in`), поэтому услуга,
     * деактивированная после записи, остаётся в опциях — иначе старую запись не сохранить.
     *
     * @return array<int, string>
     */
    private static function serviceOptions(?Booking $record): array
    {
        $attached = $record?->items->pluck('service_id')->all() ?? [];

        return BookingService::query()
            ->where(fn (Builder $query) => $query->where('is_active', true)->orWhereIn('id', $attached))
            ->orderBy('id')
            ->pluck('name', 'id')
            ->all();
    }

    /** @return array<int, string> количество услуги 1–4: границы — константы PriceCalculator */
    private static function quantityOptions(): array
    {
        return collect(range(PriceCalculator::MIN_QUANTITY, PriceCalculator::MAX_QUANTITY))
            ->mapWithKeys(fn (int $quantity): array => [$quantity => (string) $quantity])
            ->all();
    }

    /**
     * Новая услуга в строке подтягивается с готовой ценой по прайсу (услуга × радиус × тип записи).
     * Комбинации нет — цена остаётся, оператор видит предупреждение и вводит её сам.
     */
    private static function fillItemPrice(Get $get, Set $set): void
    {
        $service = self::service($get('service_id'));
        $radius = $get('../../radius');
        $carType = $get('../../car_type');

        if (! $service instanceof BookingService || ! is_numeric($radius) || ! is_string($carType)) {
            return;
        }

        $price = self::unitPrice($service, (int) $radius, CarType::from($carType));

        if ($price !== null) {
            $set('price', (string) $price->toRubles());
        }
    }

    /** Смена радиуса или типа авто: цена следует за прайсом у услуг с правилами — снимок остальных не трогаем. */
    private static function repriceItems(Get $get, Set $set): void
    {
        $items = $get('items');
        $radius = $get('radius');
        $carType = $get('car_type');

        if (! is_array($items) || ! is_numeric($radius) || ! is_string($carType)) {
            return;
        }

        foreach ($items as $key => $item) {
            $service = self::service($item['service_id'] ?? null);

            // Услуга без правил: её цена от радиуса не зависит, снимок сохраняется
            if (! $service instanceof BookingService || $service->priceRules()->doesntExist()) {
                continue;
            }

            $price = self::unitPrice($service, (int) $radius, CarType::from($carType));

            if ($price !== null) {
                $items[$key]['price'] = (string) $price->toRubles();
            }
        }

        $set('items', $items);
    }

    /**
     * Цена за единицу по прайс-правилу; null — правила есть, а комбинации нет:
     * молчаливая подстановка base_price дала бы неверную цену (потерянное правило — баг данных).
     */
    private static function unitPrice(BookingService $service, int $radius, CarType $carType): ?Money
    {
        if ($service->priceRules()->doesntExist()) {
            return $service->base_price;
        }

        try {
            $quote = app(PriceCalculator::class)->calculate(
                collect([$service]),
                $radius,
                $carType,
                [$service->id => PriceCalculator::MIN_QUANTITY],
            );
        } catch (DomainException $exception) {
            Notification::make()->warning()->title($exception->getMessage())->send();

            return null;
        }

        return $quote->lines[0]->unitPrice;
    }

    /** Итоговая стоимость записи — сумма строк (цена × количество); сервер пересчитывает её при сохранении. */
    private static function itemsTotal(mixed $items): string
    {
        $total = Money::fromKopecks(0);

        foreach (is_array($items) ? $items : [] as $item) {
            $price = $item['price'] ?? null;
            $quantity = (int) ($item['quantity'] ?? 0);

            // Недозаполненная строка итог не ломает: цена и количество проверяются при сохранении
            if (! is_numeric($price) || $price < 0 || $quantity < 1) {
                continue;
            }

            $total = $total->add(Money::fromRubles($price)->multiply($quantity));
        }

        return $total->formatted();
    }

    private static function service(mixed $serviceId): ?BookingService
    {
        if (! is_numeric($serviceId)) {
            return null;
        }

        return BookingService::query()->find((int) $serviceId);
    }
}
