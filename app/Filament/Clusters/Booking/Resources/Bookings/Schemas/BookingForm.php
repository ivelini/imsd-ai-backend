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
 * версии Filament нет): создание — клиент, время внутри часа и состав; правка — статус,
 * снимок, состав строками с ценой по прайсу и итоговая стоимость (ФТ-19).
 * Слот в форме не выбирается: на создании он приходит адресом (?slot_id=), на правке — из записи.
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
                // ФИО клиента: фамилия и имя обязательны, отчество — как получится.
                // На правке подставляется из карточки клиента (mutateFormDataBeforeFill)
                TextInput::make('surname')
                    ->label('Фамилия')
                    ->required()
                    ->maxLength(255),
                TextInput::make('name')
                    ->label('Имя')
                    ->required()
                    ->maxLength(255),
                TextInput::make('patronymic')
                    ->label('Отчество')
                    ->maxLength(255),
                TextInput::make('plate')
                    ->label('Госномер')
                    ->maxLength(20),
                // Поля «Слот» в форме нет: слот приходит адресом страницы создания (?slot_id=,
                // CreateBooking), а на правке виден датой в заголовке (EditBooking::getTitle());
                // переноса записи на другой слот (ФТ-20) нет
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
                    ->live()
                    ->afterStateUpdated(function (Set $set, mixed $state): void {
                        // Причина живёт только у отменённой записи: статус ушёл из «Отменена» — причина снимается
                        if ($state !== BookingStatus::Cancelled->value) {
                            $set('cancel_reason', null);
                        }
                    })
                    ->visibleOn('edit'),
                // Причина отмены — по статусу: у остальных статусов поля нет
                TextInput::make('cancel_reason')
                    ->label('Причина отмены')
                    ->maxLength(255)
                    ->required(fn (Get $get): bool => self::isCancelled($get))
                    ->visible(fn (Get $get): bool => self::isCancelled($get)),
                // Состав — одна механика на создании и правке: услуга подтягивается с ценой по прайсу,
                // цена правится оператором, итог считается по строкам
                Repeater::make('items')
                    ->label('Услуги')
                    ->table([
                        TableColumn::make('Услуга'),
                        TableColumn::make('Цена, ₽'),
                        TableColumn::make('Количество'),
                    ])
                    ->visible(fn (Get $get): bool => self::isFillCarParams($get))
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
                            ->default(PriceCalculator::DEFAULT_QUANTITY)
                            ->required()
                            ->live(),
                    ])
                    ->minItems(1)
                    ->required(),
                // TextEntry вместо устаревшего Placeholder, но состояние — через state():
                // content() запись не рендерит (Placeholder::content как раз оборачивал state)
                TextEntry::make('items_total')
                    ->label('Итоговая стоимость')
                    ->state(fn (Get $get): string => self::itemsTotal($get('items'))),
            ]);
    }

    /** Запись отменяется — тогда и только тогда нужна причина отмены. */
    private static function isCancelled(Get $get): bool
    {
        return $get('status') === BookingStatus::Cancelled->value;
    }

    private static function isFillCarParams(Get $get): bool
    {
        return ! empty($get('car_type')) && ! empty($get('radius'));
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
