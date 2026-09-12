<?php

namespace App\Filament\Clusters\Catalog\Resources\TireProducts\Schemas;

use App\Enums\Catalog\Season;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Validation\Rule;

class TireProductForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Описание')->schema([
                    Grid::make(2)->schema([
                        Select::make('brand_id')
                            ->label('Бренд')
                            ->relationship('brand', 'name')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->live(),
                        Select::make('model_id')
                            ->label('Модель')
                            ->relationship(
                                'model',
                                'name',
                                modifyQueryUsing: fn ($query) => $query->where('type', 'tire'),
                            )
                            ->searchable()
                            ->preload()
                            ->required()
                            ->rules([Rule::exists('product_models', 'id')->where('type', 'tire')]),
                        Select::make('season')
                            ->label('Сезон')
                            ->options(Season::class)
                            ->required(),
                        TextInput::make('name')
                            ->label('Название')
                            ->maxLength(255),
                        TextInput::make('ean')
                            ->label('EAN')
                            ->maxLength(50)
                            ->unique(ignoreRecord: true)
                            ->readonly(),
                        Select::make('country_id')
                            ->label('Страна')
                            ->relationship('country', 'name')
                            ->searchable()
                            ->preload(),
                    ]),
                ]),
                Section::make('Размеры и индексы')->schema([
                    Grid::make(4)->schema([
                        TextInput::make('width')
                            ->label('Ширина, мм')
                            ->numeric()
                            ->minValue(100)
                            ->maxValue(400),
                        TextInput::make('profile')
                            ->label('Профиль, %')
                            ->numeric()
                            ->minValue(20)
                            ->maxValue(100),
                        TextInput::make('diameter')
                            ->label('Диаметр, "')
                            ->maxLength(10),
                        TextInput::make('load_index')
                            ->label('Индекс нагрузки')
                            ->maxLength(10),
                        TextInput::make('speed_index')
                            ->label('Индекс скорости')
                            ->maxLength(5),
                    ]),
                ]),
                Section::make('Характеристики')->schema([
                    Grid::make(3)->schema([
                        Toggle::make('is_studded')
                            ->label('Шипованная'),
                        Toggle::make('is_runflat')
                            ->label('Runflat'),
                        Toggle::make('is_xl')
                            ->label('Усиленная (XL)'),
                    ]),
                ]),
                Section::make('Публикация')->schema([
                    Grid::make(3)->schema([
                        Toggle::make('is_published')
                            ->label('Опубликована')
                            ->default(true),
                        Toggle::make('is_bestseller')
                            ->label('Хит продаж'),
                        Toggle::make('is_new')
                            ->label('Новинка'),
                    ]),
                ]),
            ]);
    }
}
