<?php

namespace App\Filament\Clusters\Catalog\Resources\ProductModels\Tables;

use App\Filament\Support\PanelAction;
use App\Models\Catalog\Model\ProductModel;
use App\Preconditions\Catalog\EnsureModelHasNoProducts;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class ProductModelsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('brand.name')
                    ->label('Бренд')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('name')
                    ->label('Модель')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('slug')
                    ->label('Slug')
                    ->searchable(),
                ImageColumn::make('image')
                    ->label('Изображение'),
                TextColumn::make('type')
                    ->label('Тип')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => $state === 'tire' ? 'Шины' : 'Диски'),
            ])
            ->filters([
                SelectFilter::make('brand')
                    ->label('Бренд')
                    ->relationship('brand', 'name')
                    ->searchable(),
                SelectFilter::make('type')
                    ->label('Тип')
                    ->options([
                        'tire' => 'Шины',
                        'wheel' => 'Диски',
                    ]),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make()
                    ->action(function (ProductModel $record, EnsureModelHasNoProducts $ensure): void {
                        $model = ProductModel::withCount(['tireProducts', 'wheelProducts'])->findOrFail($record->id);

                        PanelAction::run('Модель удалена', function () use ($ensure, $model): void {
                            $ensure->ensure($model);
                            $model->delete();
                        });
                    }),
            ]);
    }
}
