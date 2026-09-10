<?php

namespace Tests\Feature\Admin\Catalog;

use App\Filament\Resources\TireProducts\Pages\CreateTireProduct;
use App\Filament\Resources\TireProducts\Pages\EditTireProduct;
use App\Filament\Resources\TireProducts\Pages\ListTireProducts;
use App\Models\Auth\Admin;
use App\Models\Catalog\Brand\Brand;
use App\Models\Catalog\Model\ProductModel;
use App\Models\Catalog\Tire\TireProduct;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\Concerns\CreatesAdmin;
use Tests\TestCase;

/** TireProductResource панели: CRUD шин, автогенерация name/slug (SEO-формула, ADR 0006). */
class TireResourceTest extends TestCase
{
    use CreatesAdmin, RefreshDatabase;

    private Admin $admin;

    private Brand $brand;

    private ProductModel $tireModel;

    private ProductModel $wheelModel;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = $this->createAdmin();

        $this->brand = Brand::factory()->create(['name' => 'Nokian', 'slug' => 'nokian']);
        $this->tireModel = ProductModel::create([
            'brand_id' => $this->brand->id, 'name' => 'Hakka', 'slug' => 'hakka', 'type' => 'tire',
        ]);
        $this->wheelModel = ProductModel::create([
            'brand_id' => $this->brand->id, 'name' => 'XX', 'slug' => 'xx', 'type' => 'wheel',
        ]);

        $this->actingAs($this->admin, 'admin');
    }

    public function test_create_tire_generates_name_and_slug(): void
    {
        Livewire::test(CreateTireProduct::class)
            ->fillForm([
                'brand_id' => $this->brand->id,
                'model_id' => $this->tireModel->id,
                'season' => 'summer',
                'width' => 215,
                'profile' => 60,
                'diameter' => '16',
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('tire_products', [
            'slug' => 'nokian-hakka-215-60-r16',
            'name' => 'Hakka',
        ]);
    }

    public function test_create_tire_rejects_wheel_model(): void
    {
        Livewire::test(CreateTireProduct::class)
            ->fillForm([
                'brand_id' => $this->brand->id,
                'model_id' => $this->wheelModel->id,
                'season' => 'summer',
            ])
            ->call('create')
            ->assertHasFormErrors(['model_id']);
    }

    public function test_create_tire_rejects_duplicate_ean(): void
    {
        TireProduct::factory()->create(['ean' => '4600000000001']);

        Livewire::test(CreateTireProduct::class)
            ->fillForm([
                'brand_id' => $this->brand->id,
                'model_id' => $this->tireModel->id,
                'season' => 'summer',
                'ean' => '4600000000001',
            ])
            ->call('create')
            ->assertHasFormErrors(['ean' => 'unique']);
    }

    public function test_create_tire_validates_season(): void
    {
        Livewire::test(CreateTireProduct::class)
            ->fillForm([
                'brand_id' => $this->brand->id,
                'model_id' => $this->tireModel->id,
                'season' => 'winter-extra',
            ])
            ->call('create')
            ->assertHasFormErrors(['season']);
    }

    public function test_update_tire_recalculates_slug(): void
    {
        $tire = TireProduct::factory()->create([
            'brand_id' => $this->brand->id,
            'model_id' => $this->tireModel->id,
            'season' => 'summer',
            'width' => 215,
            'profile' => 60,
            'diameter' => '16',
        ]);

        Livewire::test(EditTireProduct::class, ['record' => $tire->id])
            ->fillForm([
                'brand_id' => $this->brand->id,
                'model_id' => $this->tireModel->id,
                'season' => 'summer',
                'name' => 'Hakka',
                'width' => 225,
                'profile' => 60,
                'diameter' => '16',
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('tire_products', ['id' => $tire->id, 'slug' => 'nokian-hakka-225-60-r16']);
    }

    public function test_update_tire_keeps_slug_when_unchanged(): void
    {
        $tire = TireProduct::factory()->create([
            'brand_id' => $this->brand->id,
            'model_id' => $this->tireModel->id,
            'season' => 'summer',
            'slug' => 'nokian-hakka-215-60-r16',
            'width' => 215,
            'profile' => 60,
            'diameter' => '16',
        ]);

        Livewire::test(EditTireProduct::class, ['record' => $tire->id])
            ->fillForm([
                'brand_id' => $this->brand->id,
                'model_id' => $this->tireModel->id,
                'season' => 'summer',
                'name' => 'Hakka Plus',
                'width' => 215,
                'profile' => 60,
                'diameter' => '16',
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('tire_products', ['id' => $tire->id, 'slug' => 'nokian-hakka-215-60-r16']);
    }

    public function test_create_tire_gets_suffix_on_slug_collision(): void
    {
        TireProduct::factory()->create([
            'brand_id' => $this->brand->id,
            'model_id' => $this->tireModel->id,
            'ean' => 'EXISTING-TIRE',
            'season' => 'summer',
            'slug' => 'nokian-hakka-215-60-r16',
            'width' => 215,
            'profile' => 60,
            'diameter' => '16',
        ]);

        Livewire::test(CreateTireProduct::class)
            ->fillForm([
                'brand_id' => $this->brand->id,
                'model_id' => $this->tireModel->id,
                'season' => 'summer',
                'width' => 215,
                'profile' => 60,
                'diameter' => '16',
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('tire_products', ['slug' => 'nokian-hakka-215-60-r16-2']);
    }

    public function test_delete_tire_removes_record(): void
    {
        $tire = TireProduct::factory()->create(['brand_id' => $this->brand->id]);

        Livewire::test(ListTireProducts::class)
            ->callTableAction('delete', $tire);

        $this->assertDatabaseMissing('tire_products', ['id' => $tire->id]);
    }

    public function test_tire_table_searches_by_ean(): void
    {
        TireProduct::factory()->create(['brand_id' => $this->brand->id, 'name' => 'Hakka 9', 'ean' => '4600000000001']);
        TireProduct::factory()->create(['brand_id' => $this->brand->id, 'name' => 'Hakka 10', 'ean' => '4600000000002']);

        Livewire::test(ListTireProducts::class)
            ->searchTable('4600000000002')
            ->assertCanSeeTableRecords(TireProduct::where('ean', '4600000000002')->get())
            ->assertCanNotSeeTableRecords(TireProduct::where('ean', '4600000000001')->get());
    }
}
