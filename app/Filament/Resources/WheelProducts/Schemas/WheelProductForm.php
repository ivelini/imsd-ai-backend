<?php

namespace App\Filament\Resources\WheelProducts\Schemas;

use App\Enums\Catalog\WheelType;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Validation\Rule;

class WheelProductForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
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
                            modifyQueryUsing: fn ($query) => $query->where('type', 'wheel'),
                        )
                        ->searchable()
                        ->preload()
                        ->required()
                        ->rules([Rule::exists('product_models', 'id')->where('type', 'wheel')]),
                    Select::make('type')
                        ->label('Тип диска')
                        ->options(WheelType::class),
                    TextInput::make('name')
                        ->label('Название')
                        ->helperText('Пусто — подставится название модели')
                        ->maxLength(255),
                    TextInput::make('ean')
                        ->label('EAN')
                        ->maxLength(50)
                        ->unique(ignoreRecord: true),
                    Select::make('country_id')
                        ->label('Страна')
                        ->relationship('country', 'name')
                        ->searchable()
                        ->preload(),
                    TextInput::make('color')
                        ->label('Цвет')
                        ->maxLength(50),
                ]),
                Section::make('Размеры')->schema([
                    Grid::make(4)->schema([
                        TextInput::make('width')
                            ->label('Ширина, "')
                            ->numeric()
                            ->minValue(1)
                            ->maxValue(20)
                            ->step(0.1),
                        TextInput::make('diameter')
                            ->label('Диаметр, "')
                            ->numeric()
                            ->minValue(10)
                            ->maxValue(30),
                        TextInput::make('pcd')
                            ->label('PCD')
                            ->maxLength(20),
                        TextInput::make('et')
                            ->label('ET (вылет)')
                            ->numeric()
                            ->step(0.1),
                        TextInput::make('hub_diameter')
                            ->label('DIA (ступица)')
                            ->numeric()
                            ->step(0.1),
                    ]),
                ]),
                Section::make('Публикация')->schema([
                    Grid::make(3)->schema([
                        Toggle::make('is_published')
                            ->label('Опубликован')
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
