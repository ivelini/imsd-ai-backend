<?php

namespace Tests\Feature\Admin\Booking;

use App\Enums\Storage\StorageContractStatus;
use App\Filament\Clusters\Booking\Resources\StorageContracts\Pages\CreateStorageContract;
use App\Filament\Clusters\Booking\Resources\StorageContracts\Pages\EditStorageContract;
use App\Filament\Clusters\Booking\Resources\StorageContracts\Pages\ListStorageContracts;
use App\Models\Auth\Admin;
use App\Models\Storage\StorageContract;
use App\Models\Storage\StorageItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\Concerns\CreatesAdmin;
use Tests\TestCase;

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
}
