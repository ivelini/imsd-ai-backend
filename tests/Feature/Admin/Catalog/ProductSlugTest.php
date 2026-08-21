<?php

namespace Tests\Feature\Admin\Catalog;

use App\Actions\Import\Tire\UpsertTireProduct;
use App\DTOs\TireImport\ImportTireRow;
use App\Models\Auth\Admin;
use App\Models\Auth\AdminRole;
use App\Models\Catalog\Brand\Brand;
use App\Models\Catalog\Model\ProductModel;
use App\Models\Catalog\Tire\TireProduct;
use App\Models\Catalog\Wheel\WheelProduct;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Генерация slug у товаров: админка, импорт, коллизии. */
class ProductSlugTest extends TestCase
{
    use RefreshDatabase;

    private Admin $admin;

    private Brand $brand;

    private ProductModel $tireModel;

    private ProductModel $wheelModel;

    protected function setUp(): void
    {
        parent::setUp();

        $role = AdminRole::create(['name' => 'Главный администратор', 'code' => 'super-admin']);
        $this->admin = Admin::create([
            'name' => 'Admin', 'email' => 'admin@test.ru',
            'password' => bcrypt('password'), 'admin_role_id' => $role->id, 'is_active' => true,
        ]);

        $this->brand = Brand::factory()->create(['name' => 'Nokian', 'slug' => 'nokian']);
        $this->tireModel = ProductModel::create([
            'brand_id' => $this->brand->id, 'name' => 'Hakka', 'slug' => 'hakka', 'type' => 'tire',
        ]);
        $this->wheelModel = ProductModel::create([
            'brand_id' => $this->brand->id, 'name' => 'XX', 'slug' => 'xx', 'type' => 'wheel',
        ]);
    }

    public function test_tire_store_generates_slug(): void
    {
        $this->actingAs($this->admin, 'sanctum')
            ->postJson('/api/admin/catalog/tires', [
                'brand_id' => $this->brand->id,
                'model_id' => $this->tireModel->id,
                'season' => 'summer',
                'name' => 'Hakka',
                'width' => 215,
                'profile' => 60,
                'diameter' => '16',
            ])
            ->assertCreated()
            ->assertJsonPath('data.slug', 'nokian-hakka-215-60-r16');

        $this->assertDatabaseHas('tire_products', ['slug' => 'nokian-hakka-215-60-r16']);
    }

    public function test_tire_update_recalculates_slug(): void
    {
        $tire = TireProduct::factory()->create([
            'brand_id' => $this->brand->id,
            'model_id' => $this->tireModel->id,
            'season' => 'summer',
            'width' => 215,
            'profile' => 60,
            'diameter' => '16',
        ]);

        $this->actingAs($this->admin, 'sanctum')
            ->putJson("/api/admin/catalog/tires/{$tire->id}", [
                'brand_id' => $this->brand->id,
                'model_id' => $this->tireModel->id,
                'season' => 'summer',
                'name' => 'Hakka',
                'width' => 225,
                'profile' => 60,
                'diameter' => '16',
            ])
            ->assertOk()
            ->assertJsonPath('data.slug', 'nokian-hakka-225-60-r16');
    }

    public function test_tire_update_keeps_slug_when_unchanged(): void
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

        $this->actingAs($this->admin, 'sanctum')
            ->putJson("/api/admin/catalog/tires/{$tire->id}", [
                'brand_id' => $this->brand->id,
                'model_id' => $this->tireModel->id,
                'season' => 'summer',
                'name' => 'Hakka Plus',
                'width' => 215,
                'profile' => 60,
                'diameter' => '16',
            ])
            ->assertOk()
            ->assertJsonPath('data.slug', 'nokian-hakka-215-60-r16');
    }

    public function test_tire_import_generates_slug_and_name(): void
    {
        app(UpsertTireProduct::class)->execute($this->tireRow(ean: 'TIRE-1', width: 215));

        $this->assertDatabaseHas('tire_products', [
            'ean' => 'TIRE-1',
            'name' => 'Шина летняя Nokian Hakka 215/60 R16',
            'slug' => 'nokian-hakka-215-60-r16',
        ]);
    }

    public function test_tire_import_recalculates_on_reimport(): void
    {
        $upsert = app(UpsertTireProduct::class);
        $upsert->execute($this->tireRow(ean: 'TIRE-2', width: 215));
        $upsert->execute($this->tireRow(ean: 'TIRE-2', width: 225));

        $this->assertDatabaseHas('tire_products', [
            'ean' => 'TIRE-2',
            'name' => 'Шина летняя Nokian Hakka 225/60 R16',
            'slug' => 'nokian-hakka-225-60-r16',
        ]);
    }

    public function test_tire_collision_gets_suffix(): void
    {
        $upsert = app(UpsertTireProduct::class);
        $upsert->execute($this->tireRow(ean: 'TIRE-3', width: 215));
        $upsert->execute($this->tireRow(ean: 'TIRE-4', width: 215));

        $this->assertDatabaseHas('tire_products', ['ean' => 'TIRE-3', 'slug' => 'nokian-hakka-215-60-r16']);
        $this->assertDatabaseHas('tire_products', ['ean' => 'TIRE-4', 'slug' => 'nokian-hakka-215-60-r16-2']);
    }

    public function test_wheel_store_generates_slug(): void
    {
        $this->actingAs($this->admin, 'sanctum')
            ->postJson('/api/admin/catalog/wheels', [
                'brand_id' => $this->brand->id,
                'model_id' => $this->wheelModel->id,
                'name' => 'XX',
                'width' => '7',
                'diameter' => 16,
                'et' => '45',
                'pcd' => '4*98',
                'hub_diameter' => '58.6',
            ])
            ->assertCreated()
            ->assertJsonPath('data.slug', 'nokian-xx-7-16-45-4x98-58-6');
    }

    public function test_wheel_collision_gets_suffix(): void
    {
        WheelProduct::factory()->create([
            'brand_id' => $this->brand->id,
            'model_id' => $this->wheelModel->id,
            'slug' => 'nokian-xx-7-16-45-4x98-58-6',
            'width' => '7',
            'diameter' => 16,
            'et' => '45',
            'pcd' => '4*98',
            'hub_diameter' => '58.6',
        ]);

        $this->actingAs($this->admin, 'sanctum')
            ->postJson('/api/admin/catalog/wheels', [
                'brand_id' => $this->brand->id,
                'model_id' => $this->wheelModel->id,
                'name' => 'XX',
                'width' => '7',
                'diameter' => 16,
                'et' => '45',
                'pcd' => '4*98',
                'hub_diameter' => '58.6',
            ])
            ->assertCreated()
            ->assertJsonPath('data.slug', 'nokian-xx-7-16-45-4x98-58-6-2');
    }

    public function test_slug_in_admin_response(): void
    {
        $tire = TireProduct::factory()->create([
            'brand_id' => $this->brand->id,
            'model_id' => $this->tireModel->id,
            'season' => 'summer',
            'slug' => '215-60-16',
        ]);

        $this->actingAs($this->admin, 'sanctum')
            ->getJson("/api/admin/catalog/tires/{$tire->id}")
            ->assertOk()
            ->assertJsonPath('data.slug', '215-60-16');
    }

    private function tireRow(string $ean, ?int $width): ImportTireRow
    {
        return new ImportTireRow(
            ean: $ean,
            brand_name: 'Nokian',
            season_raw: 'летняя',
            country_name: null,
            name: 'Hakka',
            width: $width,
            profile: 60,
            diameter: '16',
            load_speed_index: null,
            is_runflat_raw: null,
            is_studded_raw: null,
            warehouse_name: null,
            quantity: null,
            purchase_price: null,
            minimum_market_price: null,
            euroLabel: null,
            description: null,
            description_present: false,
            origin_vendor: null,
            origin_manufacture_country: null,
            origin_manufacture_year: null,
            origin_present: false,
            promos: [],
        );
    }
}
