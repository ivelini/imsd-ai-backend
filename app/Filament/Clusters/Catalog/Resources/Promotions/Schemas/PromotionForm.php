<?php

namespace App\Filament\Clusters\Catalog\Resources\Promotions\Schemas;

use App\Enums\Promotion\PromotionType;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Relations\Relation;

class PromotionForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('Название')
                    ->required()
                    ->maxLength(255),
                Textarea::make('description')
                    ->label('Описание')
                    ->rows(2),
                Select::make('type')
                    ->label('Тип')
                    ->options(PromotionType::class)
                    ->required()
                    ->live(),
                TextInput::make('value')
                    ->label('Значение')
                    ->numeric()
                    ->minValue(0)
                    ->visible(fn (Get $get): bool => $get('type') !== PromotionType::Gift->value)
                    ->helperText(fn (Get $get): string => match ($get('type')) {
                        PromotionType::Percent->value => 'Процент скидки',
                        PromotionType::Special->value => 'Спеццена, ₽',
                        default => 'Сумма скидки, ₽',
                    }),
                DateTimePicker::make('starts_at')
                    ->label('Начало')
                    ->required()
                    ->seconds(false),
                DateTimePicker::make('ends_at')
                    ->label('Окончание')
                    ->required()
                    ->seconds(false)
                    ->afterOrEqual('starts_at'),
                // Хранится морф-алиас карты проекта (`tire`/`wheel`/`brand`), а не FQCN
                Select::make('promotable_type')
                    ->label('Привязка')
                    ->options([
                        'tire' => 'Конкретная шина',
                        'wheel' => 'Конкретный диск',
                        'brand' => 'Бренд',
                    ])
                    ->placeholder('Весь каталог')
                    ->live(),
                Select::make('promotable_id')
                    ->label('Объект')
                    ->options(fn (Get $get): array => self::promotableOptions($get('promotable_type')))
                    ->searchable()
                    ->visible(fn (Get $get): bool => filled($get('promotable_type')))
                    ->required(fn (Get $get): bool => filled($get('promotable_type'))),
            ]);
    }

    /** @return array<int, string> */
    private static function promotableOptions(?string $morphAlias): array
    {
        $model = $morphAlias !== null ? Relation::getMorphedModel($morphAlias) : null;

        if ($model === null) {
            return [];
        }

        return $model::query()->orderBy('name')->pluck('name', 'id')->all();
    }
}
