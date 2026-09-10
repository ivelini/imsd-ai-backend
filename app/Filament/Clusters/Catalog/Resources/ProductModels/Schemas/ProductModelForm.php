<?php

namespace App\Filament\Clusters\Catalog\Resources\ProductModels\Schemas;

use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Illuminate\Validation\Rules\Unique;

class ProductModelForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('brand_id')
                    ->label('Бренд')
                    ->relationship('brand', 'name')
                    ->required()
                    ->live(),
                TextInput::make('name')
                    ->label('Название')
                    ->required()
                    ->maxLength(255),
                TextInput::make('slug')
                    ->label('Slug')
                    ->required()
                    ->maxLength(255)
                    ->unique(
                        ignoreRecord: true,
                        modifyRuleUsing: fn (Unique $rule, Get $get): Unique => $rule->where('brand_id', $get('brand_id')),
                    ),
                Select::make('type')
                    ->label('Тип товара')
                    ->options([
                        'tire' => 'Шины',
                        'wheel' => 'Диски',
                    ])
                    ->required(),
                FileUpload::make('image')
                    ->label('Изображение')
                    ->image()
                    ->disk('public')
                    ->directory('models')
                    ->visibility('public')
                    ->imageEditor(),
                Textarea::make('description')
                    ->label('Описание')
                    ->columnSpanFull(),
            ]);
    }
}
