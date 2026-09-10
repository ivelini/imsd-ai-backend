<?php

namespace Tests\Feature\Admin\Catalog;

use App\Filament\Resources\Brands\Pages\CreateBrand;
use App\Filament\Resources\Brands\Pages\EditBrand;
use App\Filament\Resources\Brands\Pages\ListBrands;
use App\Models\Auth\Admin;
use App\Models\Catalog\Brand\Brand;
use App\Models\Catalog\Tire\TireProduct;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\Concerns\CreatesAdmin;
use Tests\TestCase;

/** BrandResource панели: форма, кеш-инвалидация, удаление с Precondition. */
class BrandResourceTest extends TestCase
{
    use CreatesAdmin, RefreshDatabase;

    private Admin $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = $this->createAdmin();

        $this->actingAs($this->admin, 'admin');
    }

    public function test_create_brand_invalidates_reference_cache(): void
    {
        Cache::forget('references');
        Cache::put('references', ['stale' => true]);

        Livewire::test(CreateBrand::class)
            ->fillForm([
                'name' => 'Test Brand',
                'slug' => 'test-brand',
                'type' => 'tire',
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('brands', ['slug' => 'test-brand']);
        $this->assertFalse(Cache::has('references'));
    }

    public function test_create_brand_rejects_duplicate_slug(): void
    {
        Brand::factory()->create(['slug' => 'dup-brand']);

        Livewire::test(CreateBrand::class)
            ->fillForm([
                'name' => 'Dup',
                'slug' => 'dup-brand',
                'type' => 'tire',
            ])
            ->call('create')
            ->assertHasFormErrors(['slug' => 'unique']);
    }

    public function test_update_brand_changes_fields(): void
    {
        $brand = Brand::factory()->create(['name' => 'Old Name', 'type' => 'tire']);

        Livewire::test(EditBrand::class, ['record' => $brand->id])
            ->fillForm([
                'name' => 'New Name',
                'slug' => $brand->slug,
                'type' => 'wheel',
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('brands', ['id' => $brand->id, 'name' => 'New Name', 'type' => 'wheel']);
    }

    public function test_delete_brand_without_products_removes_record(): void
    {
        $brand = Brand::factory()->create();

        Livewire::test(ListBrands::class)
            ->callTableAction('delete', $brand)
            ->assertNotified();

        $this->assertDatabaseMissing('brands', ['id' => $brand->id]);
    }

    public function test_delete_brand_blocked_when_products_exist(): void
    {
        $brand = Brand::factory()->create();
        TireProduct::factory()->create(['brand_id' => $brand->id]);

        Livewire::test(ListBrands::class)
            ->callTableAction('delete', $brand)
            ->assertNotified();

        $this->assertDatabaseHas('brands', ['id' => $brand->id]);
    }

    public function test_create_brand_stores_logo(): void
    {
        Storage::fake('public');

        Livewire::test(CreateBrand::class)
            ->fillForm([
                'name' => 'Logo Brand',
                'slug' => 'logo-brand',
                'type' => 'both',
                'logo' => UploadedFile::fake()->image('logo.jpg'),
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $brand = Brand::where('slug', 'logo-brand')->firstOrFail();
        $this->assertNotNull($brand->logo);
        Storage::disk('public')->assertExists($brand->logo);
    }

    public function test_brand_table_filters_by_type(): void
    {
        Brand::factory()->create(['name' => 'Alpha Tire', 'type' => 'tire']);
        Brand::factory()->create(['name' => 'Beta Wheel', 'type' => 'wheel']);

        Livewire::test(ListBrands::class)
            ->filterTable('type', 'wheel')
            ->assertCanSeeTableRecords(Brand::where('type', 'wheel')->get())
            ->assertCanNotSeeTableRecords(Brand::where('type', 'tire')->get());
    }
}
