<?php

namespace App\Filament\Clusters\Catalog\Resources\TireProducts\Pages;

use App\Filament\Clusters\Catalog\Resources\TireProducts\Tables\TireProductsTable;
use App\Filament\Clusters\Catalog\Resources\TireProducts\TireProductResource;
use App\Models\Delivery\City;
use Filament\Forms\Components\Select;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\EmbeddedTable;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\RenderHook;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Filament\View\PanelsRenderHook;
use Livewire\Attributes\Url;

class ListTireProducts extends ListRecords
{
    protected static string $resource = TireProductResource::class;

    /** Город страницы: для него считаются цена, срок доставки и наценка (в URL — ?cityId=). */
    #[Url]
    public ?int $cityId = null;

    public function mount(): void
    {
        parent::mount();

        // В ссылке города нет — показываем город по умолчанию из настроек магазина.
        $this->cityId ??= $this->defaultCityId();
    }

    public function table(Table $table): Table
    {
        return TireProductsTable::configure($table, $this->currentCity());
    }

    public function content(Schema $schema): Schema
    {
        return $schema
            ->components([
                $this->getTabsContentComponent(),
                RenderHook::make(PanelsRenderHook::RESOURCE_PAGES_LIST_RECORDS_TABLE_BEFORE),
                Grid::make(6)->schema([
                    Select::make('cityId')
                        ->label('Город')
                        ->options(fn (): array => City::query()->orderBy('name')->pluck('name', 'id')->all())
                        ->searchable()
                        ->live(),
                ]),
                EmbeddedTable::make(),
                RenderHook::make(PanelsRenderHook::RESOURCE_PAGES_LIST_RECORDS_TABLE_AFTER),
            ]);
    }

    /**
     * Смена города меняет цены и состав колонок — таблица пересобирается.
     *
     * Конфигурация таблицы кешируется в фазе гидратации (bootedInteractsWithTable), до применения
     * обновлённых свойств, — без пересборки в таблицу попадает предыдущий город. Пересобираются
     * только конфигурация и кеш записей; остальное состояние таблицы не трогаем.
     */
    public function updatedCityId(): void
    {
        $this->table = $this->table($this->makeTable());
        $this->flushCachedTableRecords();
        $this->resetPage();
    }

    /** Город страницы; несуществующий id (устаревшая ссылка) — как отсутствие выбора. */
    private function currentCity(): ?City
    {
        return $this->cityId !== null ? City::find($this->cityId) : null;
    }

    private function defaultCityId(): ?int
    {
        return City::query()
            ->where('name', config('shop.default_city'))
            ->value('id');
    }
}
