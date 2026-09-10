<?php

namespace Tests\Feature\Admin\Catalog;

use App\Filament\Resources\ProductModels\Pages\CreateProductModel;
use App\Filament\Resources\ProductModels\Pages\EditProductModel;
use App\Filament\Resources\ProductModels\Pages\ListProductModels;
use App\Models\Auth\Admin;
use App\Models\Catalog\Brand\Brand;
use App\Models\Catalog\Model\ProductModel;
use App\Models\Catalog\Tire\TireProduct;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\Concerns\CreatesAdmin;
use Tests\TestCase;

/** ProductModelResource панели: CRUD моделей, кеш-инвалидация references, delete с Precondition. */
class ProductModelResourceTest extends TestCase
{
    use CreatesAdmin, RefreshDatabase;

    private Admin $admin;

    private Brand $brand;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = $this->createAdmin();

        $this->brand = Brand::factory()->create(['name' => 'Brand', 'type' => 'both']);

        $this->actingAs($this->admin, 'admin');
    }

    public function test_create_model_validates_and_saves(): void
    {
        Livewire::test(CreateProductModel::class)
            ->fillForm([
                'brand_id' => $this->brand->id,
                'name' => 'A503',
                'slug' => 'a503',
                'type' => 'tire',
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('product_models', ['brand_id' => $this->brand->id, 'slug' => 'a503']);
    }

    public function test_create_model_invalidates_reference_cache(): void
    {
        Cache::forget('references');
        Cache::put('references', ['stale' => true]);

        Livewire::test(CreateProductModel::class)
            ->fillForm([
                'brand_id' => $this->brand->id,
                'name' => 'A503',
                'slug' => 'a503',
                'type' => 'tire',
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertFalse(Cache::has('references'));
    }

    public function test_create_model_rejects_duplicate_slug_within_brand(): void
    {
        ProductModel::factory()->create(['brand_id' => $this->brand->id, 'slug' => 'dup']);

        Livewire::test(CreateProductModel::class)
            ->fillForm([
                'brand_id' => $this->brand->id,
                'name' => 'Dup',
                'slug' => 'dup',
                'type' => 'tire',
            ])
            ->call('create')
            ->assertHasFormErrors(['slug' => 'unique']);
    }

    public function test_update_model_changes_fields(): void
    {
        $model = ProductModel::factory()->create(['brand_id' => $this->brand->id, 'name' => 'Старое имя', 'type' => 'tire']);

        Livewire::test(EditProductModel::class, ['record' => $model->id])
            ->fillForm([
                'brand_id' => $this->brand->id,
                'name' => 'Новое имя',
                'slug' => $model->slug,
                'type' => 'wheel',
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('product_models', ['id' => $model->id, 'name' => 'Новое имя', 'type' => 'wheel']);
    }

    public function test_delete_model_without_products_removes_record(): void
    {
        $model = ProductModel::factory()->create(['brand_id' => $this->brand->id]);

        Livewire::test(ListProductModels::class)
            ->callTableAction('delete', $model)
            ->assertNotified();

        $this->assertDatabaseMissing('product_models', ['id' => $model->id]);
    }

    public function test_delete_model_blocked_when_products_exist(): void
    {
        $model = ProductModel::factory()->create(['brand_id' => $this->brand->id]);
        TireProduct::factory()->create(['brand_id' => $this->brand->id, 'model_id' => $model->id]);

        Livewire::test(ListProductModels::class)
            ->callTableAction('delete', $model)
            ->assertNotified();

        $this->assertDatabaseHas('product_models', ['id' => $model->id]);
    }

    public function test_create_model_stores_image(): void
    {
        Storage::fake('public');

        Livewire::test(CreateProductModel::class)
            ->fillForm([
                'brand_id' => $this->brand->id,
                'name' => 'A503',
                'slug' => 'a503',
                'type' => 'tire',
                'image' => UploadedFile::fake()->image('model.jpg'),
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $model = ProductModel::where('slug', 'a503')->firstOrFail();
        $this->assertNotNull($model->image);
        Storage::disk('public')->assertExists($model->image);
    }
}
