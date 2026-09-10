<?php

namespace Tests\Feature\Admin\Catalog;

use App\Models\Auth\Admin;
use App\Models\Catalog\Brand\Brand;
use App\Models\Catalog\Tire\TireProduct;
use App\Models\Image;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesAdmin;
use Tests\TestCase;

/** Снос admin API изображений волны 2c: управление перенесено в RelationManager панели. */
class ImageApiRemovalTest extends TestCase
{
    use CreatesAdmin, RefreshDatabase;

    private Admin $admin;

    private Image $image;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = $this->createAdmin();

        $tire = TireProduct::factory()->create(['brand_id' => Brand::factory()->create()->id]);

        $this->image = Image::create([
            'imageable_type' => $tire->getMorphClass(),
            'imageable_id' => $tire->id,
            'path' => 'images/a.jpg',
            'sort' => 0,
            'is_main' => true,
        ]);
    }

    public function test_image_routes_removed(): void
    {
        $this->authGetJson('/api/admin/catalog/images')->assertNotFound();
        $this->authPostJson('/api/admin/catalog/images')->assertNotFound();
        $this->authDeleteJson("/api/admin/catalog/images/{$this->image->id}")->assertNotFound();
        $this->authPutJson("/api/admin/catalog/images/{$this->image->id}/main")->assertNotFound();
        $this->authPutJson('/api/admin/catalog/images/reorder')->assertNotFound();
    }

    public function test_unmigrated_route_still_works(): void
    {
        $this->authGetJson('/api/admin/catalog/references')->assertOk();
    }

    private function authGetJson(string $uri)
    {
        return $this->actingAs($this->admin, 'sanctum')->getJson($uri);
    }

    private function authPostJson(string $uri)
    {
        return $this->actingAs($this->admin, 'sanctum')->postJson($uri);
    }

    private function authPutJson(string $uri)
    {
        return $this->actingAs($this->admin, 'sanctum')->putJson($uri);
    }

    private function authDeleteJson(string $uri)
    {
        return $this->actingAs($this->admin, 'sanctum')->deleteJson($uri);
    }
}
