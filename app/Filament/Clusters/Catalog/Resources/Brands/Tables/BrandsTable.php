<?php

namespace App\Filament\Clusters\Catalog\Resources\Brands\Tables;

use App\Enums\Catalog\BrandType;
use App\Filament\Support\PanelAction;
use App\Models\Catalog\Brand\Brand;
use App\Preconditions\Catalog\EnsureBrandHasNoProducts;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class BrandsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Название')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('slug')
                    ->label('Slug')
                    ->searchable(),
                ImageColumn::make('logo')
                    ->label('Логотип'),
                TextColumn::make('type')
                    ->label('Тип')
                    ->badge()
                    ->formatStateUsing(fn (BrandType $state): string => $state->label()),
                TextColumn::make('created_at')
                    ->label('Создан')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('type')
                    ->label('Тип')
                    ->options(BrandType::class),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make()
                    ->action(function (Brand $record, EnsureBrandHasNoProducts $ensure): void {
                        $brand = Brand::withCount(['tireProducts', 'wheelProducts'])->findOrFail($record->id);

                        PanelAction::run('Бренд удалён', function () use ($ensure, $brand): void {
                            $ensure->ensure($brand);
                            $brand->delete();
                        });
                    }),
            ]);
    }
}
