<?php

namespace App\Filament\Support;

use App\Enums\Booking\SlotPeriod;
use Carbon\CarbonImmutable;
use Closure;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Tables\Filters\Filter;
use Illuminate\Database\Eloquent\Builder;

/**
 * Фильтр периода листинга панели: селект пресетов и два календаря «С» / «По».
 *
 * Календаря с выделением диапазона у Filament нет, поэтому дата и период — это два поля:
 * заполнено одно — выбран один день, заполнены оба — диапазон. Класс общий для листингов
 * слотов и записей: различается только то, по какой дате сужать запрос.
 */
final class PeriodFilter
{
    /** Имя фильтра: по нему страница ставит стартовое состояние (`tableFilters.period`). */
    private const NAME = 'period';

    private function __construct() {}

    /**
     * @param  Closure(Builder, CarbonImmutable, CarbonImmutable): Builder  $applyRange  сужение запроса по границам периода
     */
    public static function make(Closure $applyRange): Filter
    {
        return Filter::make(self::NAME)
            ->label('Период')
            ->columns(3)
            ->schema([
                Select::make('preset')
                    ->label('Быстрый выбор')
                    ->options(SlotPeriod::class)
                    ->maxWidth(200)
                    // Filament кастует опции энума к его кейсу — состояние приходит кейсом, а не строкой.
                    ->afterStateUpdated(function (Set $set, ?SlotPeriod $state): void {
                        if ($state === null) {
                            return;
                        }

                        $range = $state->range(CarbonImmutable::now());

                        $set('from', $range['from']->toDateString());
                        $set('until', $range['to']->toDateString());
                    }),
                DatePicker::make('from')
                    ->label('С')
                    ->displayFormat('d.m.Y')
                    ->weekStartsOnMonday()
                    ->afterStateUpdated(fn (Set $set) => $set('preset', null)),
                DatePicker::make('until')
                    ->label('По')
                    ->displayFormat('d.m.Y')
                    ->weekStartsOnMonday()
                    ->afterStateUpdated(fn (Set $set) => $set('preset', null)),
            ])
            ->query(function (Builder $query, array $data) use ($applyRange): Builder {
                $bounds = self::bounds($data);

                // Пустой период — весь список (так ведёт себя сброс фильтров).
                return $bounds === null
                    ? $query
                    : $applyRange($query, $bounds['from'], $bounds['to']);
            })
            ->indicateUsing(fn (array $data): ?string => self::indicator($data));
    }

    /**
     * Стартовое состояние фильтра — текущая неделя.
     *
     * Ставит страница в `mount()`, а не `->default()` полей: при сбросе фильтров Filament
     * перезаполняет форму дефолтами, и сброс возвращал бы неделю вместо всего списка.
     *
     * @return array{from: string, until: string, preset: string}
     */
    public static function weekDefaults(): array
    {
        $range = SlotPeriod::CurrentWeek->range(CarbonImmutable::now());

        return [
            'from' => $range['from']->toDateString(),
            'until' => $range['to']->toDateString(),
            'preset' => SlotPeriod::CurrentWeek->value,
        ];
    }

    /**
     * Границы периода из состояния фильтра: пусто — периода нет; заполнено одно поле — это один
     * день, а не «от этой даты и дальше». Границы — начало первого дня и конец последнего:
     * колонка-дата хранит время, сравнение строк день не поймает.
     *
     * @param  array<string, mixed>  $data
     * @return array{from: CarbonImmutable, to: CarbonImmutable}|null
     */
    private static function bounds(array $data): ?array
    {
        $from = $data['from'] ?? null;
        $until = $data['until'] ?? null;

        if (blank($from) && blank($until)) {
            return null;
        }

        return [
            'from' => CarbonImmutable::parse((string) ($from ?? $until))->startOfDay(),
            'to' => CarbonImmutable::parse((string) ($until ?? $from))->endOfDay(),
        ];
    }

    /** Подпись активного фильтра: «Дата: 12.09.2026» для одного дня, «Период: … — …» для диапазона. */
    private static function indicator(array $data): ?string
    {
        $bounds = self::bounds($data);

        if ($bounds === null) {
            return null;
        }

        $from = $bounds['from']->format('d.m.Y');

        if ($bounds['from']->isSameDay($bounds['to'])) {
            return sprintf('Дата: %s', $from);
        }

        return sprintf('Период: %s — %s', $from, $bounds['to']->format('d.m.Y'));
    }
}
