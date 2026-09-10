<?php

namespace Tests\Feature\Admin\Catalog;

use App\Filament\Resources\WarehouseMarkupRules\Pages\CreateWarehouseMarkupRule;
use App\Filament\Resources\WarehouseMarkupRules\Pages\EditWarehouseMarkupRule;
use App\Filament\Resources\WarehouseMarkupRules\Pages\ListWarehouseMarkupRules;
use App\Models\Auth\Admin;
use App\Models\Catalog\MarkupRule\WarehouseMarkupRule;
use App\Models\Catalog\Warehouse\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\Concerns\CreatesAdmin;
use Tests\TestCase;

/** WarehouseMarkupRuleResource панели: CRUD правил наценки. */
class MarkupRuleResourceTest extends TestCase
{
    use CreatesAdmin, RefreshDatabase;

    private Admin $admin;

    private Warehouse $warehouse;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = $this->createAdmin();

        $this->warehouse = Warehouse::factory()->create(['name' => 'Склад']);

        $this->actingAs($this->admin, 'admin');
    }

    public function test_create_markup_rule_validates_and_saves(): void
    {
        Livewire::test(CreateWarehouseMarkupRule::class)
            ->fillForm([
                'warehouse_id' => $this->warehouse->id,
                'price_from' => 0,
                'price_to' => 10000,
                'coefficient' => 1.15,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('warehouse_markup_rules', [
            'warehouse_id' => $this->warehouse->id,
            'coefficient' => 1.15,
        ]);
    }

    public function test_create_markup_rule_validates_coefficient_min(): void
    {
        Livewire::test(CreateWarehouseMarkupRule::class)
            ->fillForm([
                'warehouse_id' => $this->warehouse->id,
                'price_from' => 0,
                'price_to' => 10000,
                'coefficient' => 0.5,
            ])
            ->call('create')
            ->assertHasFormErrors(['coefficient' => 'min']);
    }

    public function test_update_markup_rule_changes_fields(): void
    {
        $rule = WarehouseMarkupRule::factory()->create([
            'warehouse_id' => $this->warehouse->id,
            'coefficient' => 1.1,
        ]);

        Livewire::test(EditWarehouseMarkupRule::class, ['record' => $rule->id])
            ->fillForm([
                'warehouse_id' => $this->warehouse->id,
                'price_from' => 0,
                'price_to' => 50000,
                'coefficient' => 1.25,
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('warehouse_markup_rules', ['id' => $rule->id, 'coefficient' => 1.25]);
    }

    public function test_delete_markup_rule_removes_record(): void
    {
        $rule = WarehouseMarkupRule::factory()->create(['warehouse_id' => $this->warehouse->id]);

        Livewire::test(ListWarehouseMarkupRules::class)
            ->callTableAction('delete', $rule);

        $this->assertDatabaseMissing('warehouse_markup_rules', ['id' => $rule->id]);
    }
}
