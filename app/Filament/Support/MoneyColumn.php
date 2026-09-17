<?php

namespace App\Filament\Support;

use App\ValueObjects\Money;
use Filament\Tables\Columns\TextColumn;

/** Колонка суммы: деньги в БД — копейки (Money), в таблице — «1 700 ₽», пустое значение — прочерк. */
final class MoneyColumn
{
    public static function make(string $name, string $label): TextColumn
    {
        return TextColumn::make($name)
            ->label($label)
            ->formatStateUsing(fn (?Money $state): string => $state?->formatted() ?? '—');
    }
}
