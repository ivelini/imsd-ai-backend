<?php

namespace App\Enums\Booking;

use App\Enums\Concerns\HasFilamentLabel;
use Filament\Support\Contracts\HasLabel;

/** Тип автомобиля клиента записи. */
enum CarType: string implements HasLabel
{
    use HasFilamentLabel;

    case Passenger = 'passenger';
    case Crossover = 'crossover';
    case Suv = 'suv';
    case Truck = 'truck';

    public function label(): string
    {
        return match ($this) {
            self::Passenger => 'Легковая',
            self::Crossover => 'Кроссовер',
            self::Suv => 'Внедорожник',
            self::Truck => 'Грузовик',
        };
    }

    /**
     * Типы, которые клиент выбирает на сайте. Грузовик — вне правил прайса:
     * грузовые авто записываются по звонку (ФТ-18 tireslot).
     *
     * @return list<self>
     */
    public static function bookable(): array
    {
        return [self::Passenger, self::Crossover, self::Suv];
    }

    /** @return list<string> значения bookable — для правил валидации */
    public static function bookableValues(): array
    {
        return array_map(fn (self $carType): string => $carType->value, self::bookable());
    }

    /** @return array<string, string> value => label — для опций форм панели */
    public static function bookableOptions(): array
    {
        return collect(self::bookable())->mapWithKeys(fn (self $carType): array => [$carType->value => $carType->label()])->all();
    }
}
