<?php

namespace Tests\Feature\Admin\Catalog;

use App\Models\Auth\Admin;
use App\Models\Auth\AdminRole;
use App\Models\Catalog\Brand;
use App\Models\Catalog\TireProduct;
use App\Models\Image;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/** HTTP-слой изображений: авторизация, основные сценарии. */
class ImageTest extends TestCase
{
    use RefreshDatabase;

    private Admin $admin;

    private TireProduct $product;

    protected function setUp(): void
    {
        parent::setUp();

        $role = AdminRole::create(['name' => 'Главный администратор', 'code' => 'super-admin']);
        $this->admin = Admin::create([
            'name' => 'Admin', 'email' => 'admin@test.ru',
            'password' => bcrypt('password'), 'admin_role_id' => $role->id, 'is_active' => true,
        ]);

        $brand = Brand::factory()->create();
        $this->product = TireProduct::factory()->create(['brand_id' => $brand->id]);
    }

    public function test_requires_auth(): void
    {
        $this->postJson('/api/admin/catalog/images')->assertUnauthorized();
        $this->getJson('/api/admin/catalog/images')->assertUnauthorized();
    }

    public function test_upload_and_list(): void
    {
        Storage::fake('public');
        $file = UploadedFile::fake()->create('test.jpg', 100, 'image/jpeg');

        $this->actingAs($this->admin, 'sanctum')
            ->postJson('/api/admin/catalog/images', [
                'image' => $file,
                'imageable_type' => 'tire',
                'imageable_id' => $this->product->id,
            ])
            ->assertCreated()
            ->assertJsonPath('data.is_main', true);

        $this->actingAs($this->admin, 'sanctum')
            ->getJson('/api/admin/catalog/images?'.http_build_query([
                'imageable_type' => 'tire',
                'imageable_id' => $this->product->id,
            ]))
            ->assertOk()
            ->assertJsonCount(1, 'data');
    }

    public function test_delete_and_main_reassign(): void
    {
        $img1 = Image::create([
            'imageable_type' => $this->product->getMorphClass(),
            'imageable_id' => $this->product->id, 'path' => 'a.jpg',
            'is_main' => true, 'sort' => 0,
        ]);
        $img2 = Image::create([
            'imageable_type' => $this->product->getMorphClass(),
            'imageable_id' => $this->product->id, 'path' => 'b.jpg',
            'is_main' => false, 'sort' => 1,
        ]);

        $this->actingAs($this->admin, 'sanctum')
            ->deleteJson("/api/admin/catalog/images/{$img1->id}")
            ->assertNoContent();

        $this->assertDatabaseHas('images', ['id' => $img2->id, 'is_main' => true]);
    }
}
