<?php

namespace Tests\Feature\Admin\Catalog;

use App\Filament\Resources\Warehouses\Pages\CreateWarehouse;
use App\Filament\Resources\Warehouses\Pages\EditWarehouse;
use App\Filament\Resources\Warehouses\Pages\ListWarehouses;
use App\Models\Auth\Admin;
use App\Models\Catalog\Warehouse\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\Concerns\CreatesAdmin;
use Tests\TestCase;

/** WarehouseResource панели: CRUD складов. */
class WarehouseResourceTest extends TestCase
{
    use CreatesAdmin, RefreshDatabase;

    private Admin $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = $this->createAdmin();

        $this->actingAs($this->admin, 'admin');
    }

    public function test_create_warehouse_validates_and_saves(): void
    {
        Livewire::test(CreateWarehouse::class)
            ->fillForm(['name' => 'Склад Опт-Трейд'])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('warehouses', ['name' => 'Склад Опт-Трейд']);
    }

    public function test_create_warehouse_requires_name(): void
    {
        Livewire::test(CreateWarehouse::class)
            ->fillForm(['name' => null])
            ->call('create')
            ->assertHasFormErrors(['name' => 'required']);
    }

    public function test_update_warehouse_changes_fields(): void
    {
        $warehouse = Warehouse::factory()->create(['name' => 'Старое имя']);

        Livewire::test(EditWarehouse::class, ['record' => $warehouse->id])
            ->fillForm(['name' => 'Новое имя'])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('warehouses', ['id' => $warehouse->id, 'name' => 'Новое имя']);
    }

    public function test_delete_warehouse_removes_record(): void
    {
        $warehouse = Warehouse::factory()->create();

        Livewire::test(ListWarehouses::class)
            ->callTableAction('delete', $warehouse);

        $this->assertDatabaseMissing('warehouses', ['id' => $warehouse->id]);
    }
}
