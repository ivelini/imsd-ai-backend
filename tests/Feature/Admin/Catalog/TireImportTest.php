<?php

namespace Tests\Feature\Admin\Catalog;

use App\Models\Auth\Admin;
use App\Models\Auth\AdminRole;
use App\Models\System\ProductImport;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/** HTTP-слой импорта: загрузка, статус, авторизация. */
class TireImportTest extends TestCase
{
    use RefreshDatabase;

    private Admin $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $role = AdminRole::create([
            'name' => 'Главный администратор',
            'code' => 'super-admin',
        ]);

        $this->admin = Admin::create([
            'name' => 'Admin',
            'email' => 'admin@test.ru',
            'password' => bcrypt('password'),
            'admin_role_id' => $role->id,
            'is_active' => true,
        ]);
    }

    public function test_upload_requires_auth(): void
    {
        $this->postJson('/api/admin/catalog/tires/import')
            ->assertUnauthorized();
    }

    public function test_upload_rejects_non_xlsx(): void
    {
        $file = UploadedFile::fake()->create('test.pdf', 100);

        $this->actingAs($this->admin, 'sanctum')
            ->postJson('/api/admin/catalog/tires/import', ['file' => $file])
            ->assertStatus(422);
    }

    public function test_upload_returns_202_and_creates_import_record(): void
    {
        Bus::fake();
        Storage::fake('local');

        $file = UploadedFile::fake()->create('tires.xlsx', 100);

        $this->actingAs($this->admin, 'sanctum')
            ->postJson('/api/admin/catalog/tires/import', ['file' => $file])
            ->assertStatus(202)
            ->assertJsonStructure(['data' => ['import_id']]);

        $this->assertDatabaseHas('product_imports', ['id' => 1, 'status' => 'pending']);
    }

    public function test_status_returns_import_data(): void
    {
        $import = ProductImport::create([
            'original_filename' => 'test.xlsx',
            'status' => 'completed',
            'total_rows' => 100,
            'processed_rows' => 100,
            'created_rows' => 80,
            'updated_rows' => 20,
            'failed_rows' => 0,
            'started_at' => now()->subMinutes(5),
            'finished_at' => now(),
        ]);

        $this->actingAs($this->admin, 'sanctum')
            ->getJson("/api/admin/catalog/tires/import/{$import->id}")
            ->assertOk()
            ->assertJsonPath('data.status', 'completed')
            ->assertJsonPath('data.total_rows', 100);
    }

    public function test_status_returns_404_for_unknown(): void
    {
        $this->actingAs($this->admin, 'sanctum')
            ->getJson('/api/admin/catalog/tires/import/999')
            ->assertNotFound();
    }
}
