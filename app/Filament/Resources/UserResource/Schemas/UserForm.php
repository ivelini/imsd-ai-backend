<?php

namespace App\Filament\Resources\UserResource\Schemas;

use App\Models\User;
use App\Support\Phone;
use Closure;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;

/**
 * Карточка клиента: ФИО, телефон и почта. Пароля нет — вход клиента в панель и личный кабинет
 * не заведены, клиент опознаётся телефоном и SMS-кодом.
 */
class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('surname')
                    ->label('Фамилия')
                    ->maxLength(255),
                TextInput::make('name')
                    ->label('Имя')
                    ->required()
                    ->maxLength(255),
                TextInput::make('patronymic')
                    ->label('Отчество')
                    ->maxLength(255),
                TextInput::make('phone')
                    ->label('Телефон')
                    ->maxLength(30)
                    // Ввод «8 (912)…» в базу не попадает: канон «7XXXXXXXXXX» — то, по чему запись находит клиента
                    ->dehydrateStateUsing(fn (?string $state): ?string => Phone::normalize($state))
                    ->rule(fn (?User $record): Closure => self::uniquePhone($record)),
                TextInput::make('email')
                    ->label('Почта')
                    ->email()
                    ->maxLength(255)
                    ->unique(ignoreRecord: true),
            ]);
    }

    /** Дубль телефона ищем по канону: «8 912 345 67 89» и «79123456789» — один и тот же клиент. */
    private static function uniquePhone(?User $record): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail) use ($record): void {
            $phone = Phone::normalize(is_string($value) ? $value : null);

            if ($phone === null) {
                return;
            }

            $duplicate = User::query()
                ->where('phone', $phone)
                ->when($record !== null, fn (Builder $query): Builder => $query->whereKeyNot($record->getKey()))
                ->exists();

            if ($duplicate) {
                $fail('Клиент с таким телефоном уже есть.');
            }
        };
    }
}
