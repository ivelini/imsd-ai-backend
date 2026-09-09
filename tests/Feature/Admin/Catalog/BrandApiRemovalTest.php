<?php

namespace Tests\Feature\Admin\Catalog;

use App\Models\Auth\Admin;
use App\Models\Auth\AdminRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Снос admin API: маршруты перенесённых на Filament разделов удалены. */
class BrandApiRemovalTest extends TestCase
{
    use RefreshDatabase;

    private Admin $admin;

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
    }

    public function test_brand_routes_removed(): void
    {
        $this->actingAs($this->admin, 'sanctum')
            ->getJson('/api/admin/catalog/brands')
            ->assertNotFound();
    }
}
