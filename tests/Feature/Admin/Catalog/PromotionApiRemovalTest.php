<?php

namespace Tests\Feature\Admin\Catalog;

use App\Models\Auth\Admin;
use App\Models\Catalog\Promotion\Promotion;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesAdmin;
use Tests\TestCase;

/** Снос admin API акций волны 2d/3: CRUD перенесён на страницы панели. */
class PromotionApiRemovalTest extends TestCase
{
    use CreatesAdmin, RefreshDatabase;

    private Admin $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = $this->createAdmin();
    }

    public function test_promotion_routes_removed(): void
    {
        $promotion = Promotion::create([
            'name' => 'Акция',
            'type' => 'percent',
            'value' => 10,
            'starts_at' => now()->subDay(),
            'ends_at' => now()->addDay(),
        ]);

        $this->authGetJson('/api/admin/catalog/promotions')->assertNotFound();
        $this->authPostJson('/api/admin/catalog/promotions')->assertNotFound();
        $this->authGetJson("/api/admin/catalog/promotions/{$promotion->id}")->assertNotFound();
        $this->authPutJson("/api/admin/catalog/promotions/{$promotion->id}")->assertNotFound();
        $this->authDeleteJson("/api/admin/catalog/promotions/{$promotion->id}")->assertNotFound();
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
