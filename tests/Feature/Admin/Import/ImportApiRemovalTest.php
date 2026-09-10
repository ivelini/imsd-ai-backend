<?php

namespace Tests\Feature\Admin\Import;

use App\Enums\Import\ImportState;
use App\Enums\Import\ImportType;
use App\Models\Auth\Admin;
use App\Models\System\ProductImport;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesAdmin;
use Tests\TestCase;

/** Снос admin API импорта волны 2d: точка входа перенесена на страницу панели. */
class ImportApiRemovalTest extends TestCase
{
    use CreatesAdmin, RefreshDatabase;

    private Admin $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = $this->createAdmin();
    }

    public function test_import_routes_removed(): void
    {
        $import = ProductImport::create([
            'original_filename' => 'tires.xlsx',
            'type' => ImportType::Tire,
            'status' => ImportState::Completed,
        ]);

        $this->authPostJson('/api/admin/catalog/import/tires')->assertNotFound();
        $this->authPostJson('/api/admin/catalog/import/wheels')->assertNotFound();
        $this->authPostJson('/api/admin/catalog/import/vehicle')->assertNotFound();
        $this->authPostJson('/api/admin/catalog/import/models')->assertNotFound();
        $this->authPostJson('/api/admin/catalog/import/geo-points')->assertNotFound();
        $this->authGetJson('/api/admin/catalog/import/status')->assertNotFound();
        $this->authGetJson("/api/admin/catalog/import/status/{$import->id}")->assertNotFound();
    }

    public function test_unmigrated_route_still_works(): void
    {
        $this->authGetJson('/api/admin/catalog/promotions')->assertOk();
    }

    private function authGetJson(string $uri)
    {
        return $this->actingAs($this->admin, 'sanctum')->getJson($uri);
    }

    private function authPostJson(string $uri)
    {
        return $this->actingAs($this->admin, 'sanctum')->postJson($uri);
    }
}
