<?php

namespace Tests\Feature\Admin\Catalog;

use App\Filament\Resources\WheelProducts\Pages\CreateWheelProduct;
use App\Filament\Resources\WheelProducts\Pages\EditWheelProduct;
use App\Filament\Resources\WheelProducts\Pages\ListWheelProducts;
use App\Models\Auth\Admin;
use App\Models\Auth\AdminRole;
use App\Models\Catalog\Brand\Brand;
use App\Models\Catalog\Model\ProductModel;
use App\Models\Catalog\Wheel\WheelProduct;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/** WheelProductResource панели: CRUD дисков, автогенерация name/slug (SEO-формула, ADR 0006). */
class WheelResourceTest extends TestCase
{
    use RefreshDatabase;

    private Admin $admin;

    private Brand $brand;

    private ProductModel $wheelModel;

    private ProductModel $tireModel;

    protected function setUp(): void
    {
        parent::setUp();

        $role = AdminRole::create(['name' => 'Главный администратор', 'code' => 'super-admin']);
        $this->admin = Admin::create([
            'name' => 'Admin',
            'email' => 'admin@test.ru',
            'password' => bcrypt('password'),
            'admin_role_id' => $role->id,
            'is_active' => true,
        ]);

        $this->brand = Brand::factory()->create(['name' => 'Nokian', 'slug' => 'nokian']);
        $this->wheelModel = ProductModel::create([
            'brand_id' => $this->brand->id, 'name' => 'XX', 'slug' => 'xx', 'type' => 'wheel',
        ]);
        $this->tireModel = ProductModel::create([
            'brand_id' => $this->brand->id, 'name' => 'Hakka', 'slug' => 'hakka', 'type' => 'tire',
        ]);

        $this->actingAs($this->admin, 'admin');
    }

    public function test_create_wheel_generates_name_and_slug(): void
    {
        Livewire::test(CreateWheelProduct::class)
            ->fillForm([
                'brand_id' => $this->brand->id,
                'model_id' => $this->wheelModel->id,
                'width' => '7',
                'diameter' => 16,
                'et' => '45',
                'pcd' => '4*98',
                'hub_diameter' => '58.6',
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('wheel_products', [
            'name' => 'XX',
            'slug' => 'nokian-xx-7-16-45-4x98-58-6',
        ]);
    }

    public function test_create_wheel_rejects_tire_model(): void
    {
        Livewire::test(CreateWheelProduct::class)
            ->fillForm([
                'brand_id' => $this->brand->id,
                'model_id' => $this->tireModel->id,
            ])
            ->call('create')
            ->assertHasFormErrors(['model_id']);
    }

    public function test_create_wheel_rejects_duplicate_ean(): void
    {
        WheelProduct::factory()->create(['ean' => '4600000000001']);

        Livewire::test(CreateWheelProduct::class)
            ->fillForm([
                'brand_id' => $this->brand->id,
                'model_id' => $this->wheelModel->id,
                'ean' => '4600000000001',
            ])
            ->call('create')
            ->assertHasFormErrors(['ean' => 'unique']);
    }

    public function test_create_wheel_validates_type(): void
    {
        Livewire::test(CreateWheelProduct::class)
            ->fillForm([
                'brand_id' => $this->brand->id,
                'model_id' => $this->wheelModel->id,
                'type' => 'carbon',
            ])
            ->call('create')
            ->assertHasFormErrors(['type']);
    }

    public function test_create_wheel_gets_suffix_on_slug_collision(): void
    {
        WheelProduct::factory()->create([
            'brand_id' => $this->brand->id,
            'model_id' => $this->wheelModel->id,
            'ean' => 'EXISTING-WHEEL',
            'slug' => 'nokian-xx-7-16-45-4x98-58-6',
            'width' => '7',
            'diameter' => 16,
            'et' => '45',
            'pcd' => '4*98',
            'hub_diameter' => '58.6',
        ]);

        Livewire::test(CreateWheelProduct::class)
            ->fillForm([
                'brand_id' => $this->brand->id,
                'model_id' => $this->wheelModel->id,
                'width' => '7',
                'diameter' => 16,
                'et' => '45',
                'pcd' => '4*98',
                'hub_diameter' => '58.6',
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('wheel_products', ['slug' => 'nokian-xx-7-16-45-4x98-58-6-2']);
    }

    public function test_update_wheel_recalculates_slug(): void
    {
        $wheel = WheelProduct::factory()->create([
            'brand_id' => $this->brand->id,
            'model_id' => $this->wheelModel->id,
            'name' => 'XX',
            'slug' => 'nokian-xx-7-16-45-4x98-58-6',
            'width' => '7',
            'diameter' => 16,
            'et' => '45',
            'pcd' => '4*98',
            'hub_diameter' => '58.6',
        ]);

        Livewire::test(EditWheelProduct::class, ['record' => $wheel->id])
            ->fillForm([
                'brand_id' => $this->brand->id,
                'model_id' => $this->wheelModel->id,
                'name' => 'XX',
                'width' => '7',
                'diameter' => 16,
                'et' => '35',
                'pcd' => '4*98',
                'hub_diameter' => '58.6',
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('wheel_products', ['id' => $wheel->id, 'slug' => 'nokian-xx-7-16-35-4x98-58-6']);
    }

    public function test_update_wheel_keeps_slug_when_unchanged(): void
    {
        $wheel = WheelProduct::factory()->create([
            'brand_id' => $this->brand->id,
            'model_id' => $this->wheelModel->id,
            'name' => 'XX',
            'slug' => 'nokian-xx-7-16-45-4x98-58-6',
            'width' => '7',
            'diameter' => 16,
            'et' => '45',
            'pcd' => '4*98',
            'hub_diameter' => '58.6',
        ]);

        Livewire::test(EditWheelProduct::class, ['record' => $wheel->id])
            ->fillForm([
                'brand_id' => $this->brand->id,
                'model_id' => $this->wheelModel->id,
                'name' => 'XX',
                'width' => '7',
                'diameter' => 16,
                'et' => '45',
                'pcd' => '4*98',
                'hub_diameter' => '58.6',
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('wheel_products', ['id' => $wheel->id, 'slug' => 'nokian-xx-7-16-45-4x98-58-6']);
    }

    public function test_delete_wheel_removes_record(): void
    {
        $wheel = WheelProduct::factory()->create(['brand_id' => $this->brand->id]);

        Livewire::test(ListWheelProducts::class)
            ->callTableAction('delete', $wheel);

        $this->assertDatabaseMissing('wheel_products', ['id' => $wheel->id]);
    }

    public function test_wheel_table_searches_by_ean(): void
    {
        WheelProduct::factory()->create(['brand_id' => $this->brand->id, 'name' => 'XX 7', 'ean' => '4600000000001']);
        WheelProduct::factory()->create(['brand_id' => $this->brand->id, 'name' => 'XX 8', 'ean' => '4600000000002']);

        Livewire::test(ListWheelProducts::class)
            ->searchTable('4600000000002')
            ->assertCanSeeTableRecords(WheelProduct::where('ean', '4600000000002')->get())
            ->assertCanNotSeeTableRecords(WheelProduct::where('ean', '4600000000001')->get());
    }
}
