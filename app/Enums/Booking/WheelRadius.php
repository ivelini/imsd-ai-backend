<?php

namespace App\Enums\Booking;

use App\Enums\Concerns\HasFilamentLabel;
use Filament\Support\Contracts\HasLabel;

/**
 * Радиусы колёс, предлагаемые клиенту на сайте (R13–R21).
 * В БД (booking_price_rules, bookings) хранится int-значение радиуса.
 * R22 в прайсе нет (группы до R21) — R22 не предлагается.
 */
enum WheelRadius: int implements HasLabel
{
    use HasFilamentLabel;

    case R13 = 13;
    case R14 = 14;
    case R15 = 15;
    case R16 = 16;
    case R17 = 17;
    case R18 = 18;
    case R19 = 19;
    case R20 = 20;
    case R21 = 21;

    public function label(): string
    {
        return 'R'.$this->value;
    }

    /** @return array<int, string> value => label — для опций форм панели */
    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn (self $radius): array => [$radius->value => $radius->label()])->all();
    }
}
