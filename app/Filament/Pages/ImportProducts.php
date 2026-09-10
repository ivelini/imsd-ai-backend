<?php

namespace App\Filament\Pages;

use App\Actions\Import\StartProductImport;
use App\DTOs\Import\StartImportInput;
use App\Enums\Import\ImportState;
use App\Enums\Import\ImportType;
use App\Filament\Support\PanelAction;
use App\Models\System\ProductImport;
use App\Preconditions\Import\EnsureNoActiveImport;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Pages\Page;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use UnitEnum;

/**
 * Загрузка файлов импорта и история запусков.
 *
 * @property Table $table
 * @property Schema $form
 */
class ImportProducts extends Page implements HasTable
{
    use InteractsWithTable;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedArrowUpTray;

    protected static string|UnitEnum|null $navigationGroup = 'Каталог';

    protected static ?string $title = 'Импорт';

    protected static ?string $navigationLabel = 'Импорт';

    protected static ?string $slug = 'import';

    protected string $view = 'filament.pages.import-products';

    /** @var array<string, mixed> */
    public array $data = [];

    public function mount(): void
    {
        $this->form->fill();
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('type')
                    ->label('Тип импорта')
                    ->options(fn (): array => collect(ImportType::cases())
                        ->mapWithKeys(fn (ImportType $type): array => [$type->value => $type->label()])
                        ->all())
                    ->required()
                    ->live(),
                FileUpload::make('file')
                    ->label('Файл')
                    ->storeFiles(false)
                    ->required()
                    ->acceptedFileTypes(fn (): array => self::acceptedMimeTypes($this->selectedType()))
                    ->maxSize(51200)
                    ->helperText(fn (): string => $this->selectedType() === ImportType::Vehicle
                        ? 'CSV, до 50 МБ'
                        : 'XLSX, до 50 МБ'),
            ])
            ->statePath('data');
    }

    /** Тип выбранного импорта: по умолчанию шины. */
    private function selectedType(): ImportType
    {
        return ImportType::tryFrom((string) ($this->data['type'] ?? '')) ?? ImportType::Tire;
    }

    /** @return string[] Допустимые MIME-типы файла для типа импорта. */
    private static function acceptedMimeTypes(ImportType $type): array
    {
        return $type === ImportType::Vehicle
            ? ['text/csv', 'text/plain', 'application/csv']
            : ['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'application/zip'];
    }

    public function startImport(): void
    {
        $data = $this->form->getState();
        $type = ImportType::from($data['type']);

        /** @var TemporaryUploadedFile $file */
        $file = $data['file'];

        PanelAction::run('Импорт запущен', function () use ($type, $file): void {
            app(EnsureNoActiveImport::class)->ensure($type);

            app(StartProductImport::class)->execute(new StartImportInput(
                file: $file,
                type: $type,
            ));
        });

        $this->form->fill();
        $this->resetTable();
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(ProductImport::query()->latest('id'))
            ->poll('5s')
            ->columns([
                TextColumn::make('id')
                    ->label('ID')
                    ->sortable(),
                TextColumn::make('type')
                    ->label('Тип')
                    ->badge()
                    ->formatStateUsing(fn (ImportType $state): string => $state->label()),
                TextColumn::make('original_filename')
                    ->label('Файл')
                    ->searchable(),
                TextColumn::make('status')
                    ->label('Статус')
                    ->badge()
                    ->formatStateUsing(fn (ImportState $state): string => $state->label())
                    ->color(fn (ImportState $state): string => match ($state) {
                        ImportState::Completed => 'success',
                        ImportState::Failed => 'danger',
                        ImportState::Processing => 'warning',
                        ImportState::Pending => 'gray',
                    }),
                TextColumn::make('progress')
                    ->label('Строки')
                    ->state(fn (ProductImport $record): string => $record->processed_rows.'/'.($record->total_rows ?: '—')),
                TextColumn::make('failed_rows')
                    ->label('Ошибки')
                    ->color(fn (ProductImport $record): string => $record->failed_rows > 0 ? 'danger' : 'gray'),
                TextColumn::make('error_message')
                    ->label('Сообщение')
                    ->limit(60)
                    ->tooltip(fn (ProductImport $record): ?string => $record->error_message)
                    ->toggleable(),
                TextColumn::make('created_at')
                    ->label('Запущен')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('finished_at')
                    ->label('Завершён')
                    ->dateTime()
                    ->placeholder('—')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('type')
                    ->label('Тип')
                    ->options(fn (): array => collect(ImportType::cases())
                        ->mapWithKeys(fn (ImportType $type): array => [$type->value => $type->label()])
                        ->all()),
            ])
            ->recordActions([
                Action::make('errors')
                    ->label('Ошибки')
                    ->icon(Heroicon::OutlinedExclamationTriangle)
                    ->visible(fn (ProductImport $record): bool => ! empty($record->errors))
                    ->modalHeading('Ошибки импорта')
                    ->modalContent(fn (ProductImport $record) => view('filament.pages.import-errors', [
                        'errors' => $record->errors ?? [],
                    ]))
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Закрыть'),
            ])
            ->defaultSort('id', 'desc');
    }
}
