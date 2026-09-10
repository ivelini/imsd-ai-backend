<?php

namespace Tests\Feature\Admin\Catalog;

use App\Models\Auth\Admin;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesAdmin;
use Tests\TestCase;

/** Снос admin API: маршруты перенесённых на Filament разделов удалены. */
class BrandApiRemovalTest extends TestCase
{
    use CreatesAdmin, RefreshDatabase;

    private Admin $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = $this->createAdmin();
    }

    public function test_brand_routes_removed(): void
    {
        $this->actingAs($this->admin, 'sanctum')
            ->getJson('/api/admin/catalog/brands')
            ->assertNotFound();
    }
}
