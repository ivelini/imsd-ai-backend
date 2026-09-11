<?php

namespace App\Filament\Clusters\Catalog\Resources\Products\RelationManagers;

use App\Actions\Image\DeleteImage;
use App\Actions\Image\ReorderImages;
use App\Actions\Image\SetMainImage;
use App\Actions\Image\UploadImage;
use App\DTOs\Image\UploadImageInput;
use App\Filament\Support\PanelAction;
use App\Services\Admin\ImageService;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Forms\Components\FileUpload;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

/** Изображения товара (общий для шин и дисков): загрузка, порядок, главное, удаление. */
class ImagesRelationManager extends RelationManager
{
    protected static string $relationship = 'images';

    protected static ?string $title = 'Изображения';

    public function table(Table $table): Table
    {
        return $table
            ->reorderable('sort')
            ->afterReordering(function (array $order): void {
                // Порядок пишет тот же Action, что и API: встроенная SQL-запись Filament обошла бы слой.
                app(ReorderImages::class)->execute(array_values($order));
            })
            ->columns([
                ImageColumn::make('path')
                    ->label('Изображение')
                    ->disk('public'),
                TextColumn::make('sort')
                    ->label('Порядок')
                    ->sortable(),
                IconColumn::make('is_main')
                    ->label('Главное')
                    ->boolean(),
            ])
            ->headerActions([
                CreateAction::make()
                    ->label('Загрузить')
                    ->schema([
                        FileUpload::make('path')
                            ->label('Файл')
                            ->image()
                            ->storeFiles(false)
                            ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                            ->maxSize(10240)
                            ->required(),
                    ])
                    ->action(function (array $data, RelationManager $livewire): void {
                        $owner = $livewire->getOwnerRecord();

                        /** @var TemporaryUploadedFile $file */
                        $file = $data['path'];

                        PanelAction::run('Изображение загружено', fn () => app(UploadImage::class)->execute(new UploadImageInput(
                            imageableType: $owner->getMorphClass(),
                            imageableId: (int) $owner->getKey(),
                            file: $file,
                        )));
                    }),
            ])
            ->recordActions([
                Action::make('setMain')
                    ->label('Сделать главным')
                    ->icon(Heroicon::OutlinedStar)
                    ->visible(fn (Model $record): bool => ! $record->getAttribute('is_main'))
                    ->action(fn (Model $record) => app(SetMainImage::class)->execute((int) $record->getKey())),
                DeleteAction::make()
                    ->action(fn (Model $record) => app(DeleteImage::class)->execute((int) $record->getKey())),
            ]);
    }

    public static function getMaxImages(): int
    {
        return ImageService::MAX_IMAGES;
    }
}
