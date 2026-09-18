<?php

namespace Tests\Feature\Admin\Booking;

use App\Enums\Storage\StorageContractStatus;
use App\Filament\Clusters\Booking\Resources\StorageContracts\Pages\CreateStorageContract;
use App\Filament\Clusters\Booking\Resources\StorageContracts\Pages\EditStorageContract;
use App\Filament\Clusters\Booking\Resources\StorageContracts\Pages\ListStorageContracts;
use App\Filament\Resources\UserResource;
use App\Models\Auth\Admin;
use App\Models\Storage\StorageContract;
use App\Models\Storage\StorageItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\Concerns\CreatesAdmin;
use Tests\TestCase;
use ZipArchive;

/** StorageContractResource панели: договор хранения — клиент, срок, стоимость, позиции, закрытие. */
class StorageContractResourceTest extends TestCase
{
    use CreatesAdmin, RefreshDatabase;

    private Admin $admin;

    private User $client;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = $this->createAdmin();
        $this->actingAs($this->admin, 'admin');

        $this->client = User::factory()->bookingClient()->create([
            'name' => 'Иван',
            'surname' => 'Петров',
        ]);
    }

    /** @return array<string, mixed> */
    private function formData(): array
    {
        return [
            'user_id' => $this->client->id,
            'personal_document' => '75 18 074294',
            'starts_on' => '2026-10-01',
            'ends_on' => '2027-04-30',
            'price' => '6000',
            'items' => [
                ['name' => 'Комплект литых дисков R17 с шинами', 'description' => 'царапина на диске 2'],
            ],
        ];
    }

    /** Договор клиента из setUp с двумя позициями — для тестов правки, закрытия и удаления. */
    private function contractWithItems(): StorageContract
    {
        $contract = StorageContract::factory()->create(['user_id' => $this->client->id]);

        StorageItem::create([
            'storage_contract_id' => $contract->id,
            'name' => 'Литые диски R17',
            'description' => 'старое описание',
        ]);
        StorageItem::create([
            'storage_contract_id' => $contract->id,
            'name' => 'Колпаки',
            'description' => null,
        ]);

        return $contract;
    }

    public function test_store_saves_contract_with_price_in_kopecks(): void
    {
        Livewire::test(CreateStorageContract::class)
            ->fillForm($this->formData())
            ->call('create')
            ->assertHasNoFormErrors();

        $contract = StorageContract::firstOrFail();
        $this->assertSame($this->client->id, $contract->user_id);
        $this->assertSame('75 18 074294', $contract->personal_document);
        $this->assertSame('2026-10-01', $contract->starts_on->toDateString());
        $this->assertSame('2027-04-30', $contract->ends_on->toDateString());
        $this->assertSame(600000, (int) $contract->getRawOriginal('price')); // в БД — копейки
        $this->assertSame(600000, $contract->price->toKopecks());
        $this->assertSame(6000.0, $contract->price->toRubles());
        $this->assertSame(StorageContractStatus::Active, $contract->status);
    }

    public function test_store_rejects_end_before_start(): void
    {
        Livewire::test(CreateStorageContract::class)
            ->fillForm([...$this->formData(), 'starts_on' => '2027-04-30', 'ends_on' => '2026-10-01'])
            ->call('create')
            ->assertHasFormErrors(['ends_on']);

        $this->assertSame(0, StorageContract::count());
    }

    /** Сдал и забрал в один день — срок нулевой длины допустим: строгая проверка «после» этот тест завалит. */
    public function test_store_allows_equal_start_and_end(): void
    {
        Livewire::test(CreateStorageContract::class)
            ->fillForm([...$this->formData(), 'starts_on' => '2026-10-01', 'ends_on' => '2026-10-01'])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertSame(1, StorageContract::count());
    }

    public function test_store_requires_client(): void
    {
        Livewire::test(CreateStorageContract::class)
            ->fillForm([...$this->formData(), 'user_id' => null])
            ->call('create')
            ->assertHasFormErrors(['user_id']);

        $this->assertSame(0, StorageContract::count());
    }

    /** Документ, удостоверяющий личность, обязателен: без него договор не заводят */
    public function test_store_requires_personal_document(): void
    {
        Livewire::test(CreateStorageContract::class)
            ->fillForm([...$this->formData(), 'personal_document' => null])
            ->call('create')
            ->assertHasFormErrors(['personal_document']);

        $this->assertSame(0, StorageContract::count());
    }

    /** Оператора проставляет панель из авторизации — из формы поле не приходит. */
    public function test_store_sets_operator_from_auth(): void
    {
        Livewire::test(CreateStorageContract::class)
            ->fillForm($this->formData())
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertSame($this->admin->id, StorageContract::firstOrFail()->operator_id);
    }

    public function test_store_saves_items_with_optional_description(): void
    {
        Livewire::test(CreateStorageContract::class)
            ->fillForm([...$this->formData(), 'items' => [
                ['name' => 'Литые диски R17 с шинами', 'description' => 'царапина на диске 2'],
                ['name' => 'Колпаки', 'description' => ''],
            ]])
            ->call('create')
            ->assertHasNoFormErrors();

        $items = StorageContract::firstOrFail()->items()->orderBy('id')->get();
        $this->assertCount(2, $items);
        $this->assertSame('Литые диски R17 с шинами', $items->first()->name);
        $this->assertSame('Колпаки', $items->last()->name);
        $this->assertEmpty($items->last()->description);
    }

    public function test_store_requires_at_least_one_item(): void
    {
        Livewire::test(CreateStorageContract::class)
            ->fillForm([...$this->formData(), 'items' => []])
            ->call('create')
            ->assertHasFormErrors(['items']);

        $this->assertSame(0, StorageContract::count());
    }

    /** Правка синхронизирует позиции: изменённая обновляется по id, убранная удаляется, новая создаётся. */
    public function test_edit_syncs_items(): void
    {
        $contract = $this->contractWithItems();
        $updated = $contract->items()->orderBy('id')->firstOrFail();
        $removed = $contract->items()->orderByDesc('id')->firstOrFail();

        $component = Livewire::test(EditStorageContract::class, ['record' => $contract->id]);
        $component->set('data.items', [
            "record-{$updated->id}" => ['name' => 'Литые диски R17', 'description' => 'новое описание'],
            'new-item' => ['name' => 'Секретки', 'description' => null],
        ]);
        $component->call('save')->assertHasNoFormErrors();

        $items = $contract->fresh()->items()->orderBy('id')->get();
        $this->assertCount(2, $items);
        $this->assertTrue($items->contains('id', $updated->id)); // обновлена, не пересоздана
        $this->assertSame('новое описание', $updated->fresh()->description);
        $this->assertFalse($items->contains('id', $removed->id)); // убранная удалена
        $this->assertTrue($items->contains('name', 'Секретки'));
    }

    public function test_close_sets_status_and_date(): void
    {
        $contract = StorageContract::factory()->create(['user_id' => $this->client->id]);

        Livewire::test(ListStorageContracts::class)
            ->callTableAction('close', $contract)
            ->assertHasNoTableActionErrors();

        $this->assertSame(StorageContractStatus::Closed, $contract->fresh()->status);
        $this->assertNotNull($contract->fresh()->closed_at);

        // Закрытый договор повторно не закрывают
        Livewire::test(ListStorageContracts::class)
            ->assertTableActionHidden('close', $contract->fresh());
    }

    public function test_delete_cascades_items(): void
    {
        $contract = $this->contractWithItems();
        $this->assertSame(2, StorageItem::count());

        $contract->delete();

        $this->assertSame(0, StorageItem::count());
        $this->assertNotNull($this->client->fresh());
    }

    /** Номер виден в списке: оператор называет договор по номеру, а не по клиенту */
    public function test_list_shows_contract_number(): void
    {
        $contract = StorageContract::factory()->create(['user_id' => $this->client->id]);

        Livewire::test(ListStorageContracts::class)
            ->assertTableColumnStateSet('number', sprintf('%05d', $contract->id), $contract);
    }

    public function test_edit_title_shows_number_and_client(): void
    {
        $contract = StorageContract::factory()->create(['user_id' => $this->client->id]);

        $title = Livewire::test(EditStorageContract::class, ['record' => $contract->id])->instance()->getTitle();

        $this->assertStringContainsString(sprintf('Договор № %05d', $contract->id), $title);
        $this->assertStringContainsString('Петров', $title);
    }

    /** Переход из записи: адрес несёт клиента, срок «с» и стоимость — форма открывается заполненной */
    public function test_create_prefills_from_query(): void
    {
        Livewire::withQueryParams([
            'user_id' => $this->client->id,
            'starts_on' => '2026-10-01',
            'price' => '6000',
        ])->test(CreateStorageContract::class)
            ->assertFormSet([
                'user_id' => $this->client->id,
                'starts_on' => '2026-10-01',
                'price' => '6000',
            ]);
    }

    /** Переход из меню: подставлять клиента неоткуда — поле остаётся пустым */
    public function test_create_without_query_leaves_client_empty(): void
    {
        Livewire::test(CreateStorageContract::class)
            ->assertFormSet(['user_id' => null]);
    }

    /** Нового клиента заводят, не бросая заполненный договор: форма создания — в новой вкладке */
    public function test_create_has_button_to_create_client(): void
    {
        Livewire::test(CreateStorageContract::class)
            ->assertActionExists('createClient')
            ->assertActionHasUrl('createClient', UserResource::getUrl('create'))
            ->assertActionShouldOpenUrlInNewTab('createClient')
            ->assertSee('Создать пользователя'); // кнопка отрисована, а не только объявлена
    }

    /** Нумерация начинается со 100: последовательность без перезапуска отдала бы id = 1 */
    public function test_store_numbers_contracts_from_hundred(): void
    {
        Livewire::test(CreateStorageContract::class)
            ->fillForm($this->formData())
            ->call('create')
            ->assertHasNoFormErrors();

        $contract = StorageContract::firstOrFail();
        $this->assertGreaterThanOrEqual(100, $contract->id);
        $this->assertSame(sprintf('%05d', $contract->id), $contract->number);
    }

    /** Листинг ищет договор по номеру — и по полному «00101», и по цифрам без ведущих нулей */
    public function test_list_search_finds_by_number(): void
    {
        StorageContract::factory()->count(2)->create(['user_id' => $this->client->id]);
        $contract = StorageContract::orderByDesc('id')->firstOrFail();

        Livewire::test(ListStorageContracts::class)
            ->searchTable($contract->number)
            ->assertCanSeeTableRecords([$contract])
            ->assertCanNotSeeTableRecords([StorageContract::orderBy('id')->firstOrFail()]);

        Livewire::test(ListStorageContracts::class)
            ->searchTable(ltrim($contract->number, '0'))
            ->assertCanSeeTableRecords([$contract]);
    }

    /** Листинг ищет и по телефону клиента: оператор ищет договор по номеру из трубки */
    public function test_list_search_finds_by_phone(): void
    {
        $contract = StorageContract::factory()->create([
            'user_id' => User::factory()->bookingClient()->create(['phone' => '79001234567'])->id,
        ]);
        $other = StorageContract::factory()->create([
            'user_id' => User::factory()->bookingClient()->create(['phone' => '79131112233'])->id,
        ]);

        Livewire::test(ListStorageContracts::class)
            ->searchTable('79001234567')
            ->assertCanSeeTableRecords([$contract])
            ->assertCanNotSeeTableRecords([$other]);
    }

    /** Кнопка отдаёт файл, а не уводит со страницы: имя — с номером договора, содержимое — docx */
    public function test_print_downloads_document(): void
    {
        $contract = $this->contractWithItems();

        $component = Livewire::test(EditStorageContract::class, ['record' => $contract->id])
            ->assertActionVisible('printDocument')
            ->call('printDocument');

        $component->assertFileDownloaded("Договор хранения №{$contract->number}.docx");

        // Содержимое — байты .docx: номер лежит в документе внутри zip, в сырых байтах его не видно
        $content = base64_decode((string) $component->effects['download']['content']);
        $path = tempnam(sys_get_temp_dir(), 'docx');
        file_put_contents($path, $content);

        $zip = new ZipArchive;
        $zip->open($path);
        $documentXml = (string) $zip->getFromName('word/document.xml');
        $zip->close();
        unlink($path);

        $this->assertStringStartsWith('PK', $content);
        $this->assertStringContainsString($contract->number, $documentXml);
    }
}
