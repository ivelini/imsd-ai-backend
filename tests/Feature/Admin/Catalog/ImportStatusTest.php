<?php

declare(strict_types=1);

namespace Tests\Feature\Admin\Catalog;

use App\Enums\Import\ImportState;
use App\Enums\Import\ImportType;
use App\Models\Auth\Admin;
use App\Models\Auth\AdminRole;
use App\Models\System\ProductImport;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class ImportStatusTest extends TestCase
{
    use RefreshDatabase;

    private Admin $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $role = AdminRole::create(['name' => 'Super Admin', 'code' => 'super-admin']);
        $this->admin = Admin::create([
            'name' => 'Test Admin',
            'email' => 'test@example.com',
            'password' => bcrypt('password'),
            'admin_role_id' => $role->id,
        ]);
    }

    public function test_status_requires_auth(): void
    {
        $this->getJson('/api/admin/catalog/import/status')
            ->assertUnauthorized();
    }

    public function test_status_returns_latest_per_type(): void
    {
        // Старый импорт шин — не должен быть в ответе
        ProductImport::create([
            'original_filename' => 'old.xlsx',
            'type' => ImportType::Tire,
            'status' => ImportState::Completed,
            'processed_rows' => 10,
            'finished_at' => now()->subHour(),
        ]);

        // Новый импорт шин — должен быть в ответе
        ProductImport::create([
            'original_filename' => 'new.xlsx',
            'type' => ImportType::Tire,
            'status' => ImportState::Completed,
            'processed_rows' => 50,
            'error_message' => null,
            'errors' => null,
            'finished_at' => now(),
        ]);

        // Импорт дисков
        ProductImport::create([
            'original_filename' => 'wheels.xlsx',
            'type' => ImportType::Wheel,
            'status' => ImportState::Completed,
            'processed_rows' => 30,
            'error_message' => 'Ошибка парсинга',
            'errors' => ['row 5: bad data'],
            'finished_at' => now()->subMinutes(10),
        ]);

        $response = $this->actingAs($this->admin, 'sanctum')
            ->getJson('/api/admin/catalog/import/status')
            ->assertOk();

        $data = $response->json('data');

        $this->assertIsArray($data);
        $this->assertGreaterThanOrEqual(2, count($data));

        $tire = collect($data)->firstWhere('type', 'tire');
        $this->assertNotNull($tire);
        $this->assertSame(50, $tire['processed_rows']);

        $wheel = collect($data)->firstWhere('type', 'wheel');
        $this->assertNotNull($wheel);
        $this->assertSame(30, $wheel['processed_rows']);
        $this->assertSame('Ошибка парсинга', $wheel['error_message']);
        $this->assertSame(['row 5: bad data'], $wheel['errors']);
    }

    public function test_status_empty_when_no_imports(): void
    {
        $this->actingAs($this->admin, 'sanctum')
            ->getJson('/api/admin/catalog/import/status')
            ->assertOk()
            ->assertJsonPath('data', []);
    }
}
