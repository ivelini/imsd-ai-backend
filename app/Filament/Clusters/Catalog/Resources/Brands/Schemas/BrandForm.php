<?php

namespace App\Filament\Clusters\Catalog\Resources\Brands\Schemas;

use App\Enums\Catalog\BrandType;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class BrandForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('Название')
                    ->required()
                    ->maxLength(255),
                TextInput::make('slug')
                    ->label('Slug')
                    ->required()
                    ->maxLength(255)
                    ->unique(ignoreRecord: true),
                Select::make('type')
                    ->label('Тип')
                    ->options(BrandType::class)
                    ->default(BrandType::Tire)
                    ->required(),
                FileUpload::make('logo')
                    ->label('Логотип')
                    ->image()
                    ->disk('public')
                    ->directory('brands')
                    ->visibility('public')
                    ->imageEditor(),
                Textarea::make('description')
                    ->label('Описание')
                    ->columnSpanFull(),
            ]);
    }
}
