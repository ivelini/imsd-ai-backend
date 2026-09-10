<?php

namespace Tests\Feature\Admin\Import;

use App\Enums\Import\ImportState;
use App\Enums\Import\ImportType;
use App\Filament\Clusters\Catalog\Pages\ImportProducts;
use App\Jobs\CatalogImport\ImportMasterJob;
use App\Jobs\VehicleImport\VehicleImportMasterJob;
use App\Models\System\ProductImport;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\Concerns\CreatesAdmin;
use Tests\TestCase;

/** Страница импорта панели: запуск потоков, блокировка повторного запуска, история. */
class ImportPageTest extends TestCase
{
    use CreatesAdmin, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Bus::fake();
        Storage::fake('local');

        $this->actingAs($this->createAdmin(), 'admin');
    }

    public function test_upload_tire_creates_import_and_dispatches_job(): void
    {
        Livewire::test(ImportProducts::class)
            ->fillForm([
                'type' => ImportType::Tire->value,
                'file' => UploadedFile::fake()->create('tires.xlsx', 100),
            ])
            ->call('startImport')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('product_imports', [
            'type' => ImportType::Tire->value,
            'status' => ImportState::Pending->value,
        ]);

        Bus::assertDispatched(ImportMasterJob::class);
    }

    public function test_upload_rejects_non_xlsx_for_tire(): void
    {
        Livewire::test(ImportProducts::class)
            ->fillForm([
                'type' => ImportType::Tire->value,
                'file' => UploadedFile::fake()->create('test.pdf', 100),
            ])
            ->call('startImport')
            ->assertHasFormErrors(['file']);

        $this->assertDatabaseCount('product_imports', 0);
    }

    public function test_upload_vehicle_accepts_csv(): void
    {
        Livewire::test(ImportProducts::class)
            ->fillForm([
                'type' => ImportType::Vehicle->value,
                'file' => UploadedFile::fake()->create('vehicle.csv', 100),
            ])
            ->call('startImport')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('product_imports', [
            'type' => ImportType::Vehicle->value,
            'status' => ImportState::Pending->value,
        ]);

        Bus::assertDispatched(VehicleImportMasterJob::class);
    }

    public function test_upload_vehicle_rejects_xlsx(): void
    {
        Livewire::test(ImportProducts::class)
            ->fillForm([
                'type' => ImportType::Vehicle->value,
                'file' => UploadedFile::fake()->create('vehicle.xlsx', 100),
            ])
            ->call('startImport')
            ->assertHasFormErrors(['file']);
    }

    public function test_active_import_blocks_same_type(): void
    {
        ProductImport::create([
            'original_filename' => 'running.xlsx',
            'type' => ImportType::Tire,
            'status' => ImportState::Pending,
        ]);

        Livewire::test(ImportProducts::class)
            ->fillForm([
                'type' => ImportType::Tire->value,
                'file' => UploadedFile::fake()->create('tires.xlsx', 100),
            ])
            ->call('startImport');

        $this->assertDatabaseCount('product_imports', 1);
        Bus::assertNothingDispatched();
    }

    public function test_active_import_of_other_type_does_not_block(): void
    {
        ProductImport::create([
            'original_filename' => 'wheels.xlsx',
            'type' => ImportType::Wheel,
            'status' => ImportState::Processing,
        ]);

        Livewire::test(ImportProducts::class)
            ->fillForm([
                'type' => ImportType::Tire->value,
                'file' => UploadedFile::fake()->create('tires.xlsx', 100),
            ])
            ->call('startImport')
            ->assertHasNoFormErrors();

        $this->assertDatabaseCount('product_imports', 2);
        $this->assertDatabaseHas('product_imports', ['type' => ImportType::Tire->value]);
    }

    public function test_completed_import_does_not_block(): void
    {
        ProductImport::create([
            'original_filename' => 'done.xlsx',
            'type' => ImportType::Tire,
            'status' => ImportState::Completed,
        ]);

        Livewire::test(ImportProducts::class)
            ->fillForm([
                'type' => ImportType::Tire->value,
                'file' => UploadedFile::fake()->create('tires.xlsx', 100),
            ])
            ->call('startImport')
            ->assertHasNoFormErrors();

        $this->assertDatabaseCount('product_imports', 2);
    }

    public function test_table_shows_import_history(): void
    {
        $tire = $this->createImport(ImportType::Tire, 'tires.xlsx');
        $wheel = $this->createImport(ImportType::Wheel, 'wheels.xlsx');
        $vehicle = $this->createImport(ImportType::Vehicle, 'vehicle.csv');

        Livewire::test(ImportProducts::class)
            ->assertCanSeeTableRecords([$tire, $wheel, $vehicle]);
    }

    public function test_table_filters_by_type(): void
    {
        $tire = $this->createImport(ImportType::Tire, 'tires.xlsx');
        $wheel = $this->createImport(ImportType::Wheel, 'wheels.xlsx');

        Livewire::test(ImportProducts::class)
            ->filterTable('type', ImportType::Wheel->value)
            ->assertCanSeeTableRecords([$wheel])
            ->assertCanNotSeeTableRecords([$tire]);
    }

    public function test_page_requires_auth(): void
    {
        auth('admin')->logout();

        $this->get(ImportProducts::getUrl())->assertRedirect('/panel/login');
    }

    private function createImport(ImportType $type, string $filename): ProductImport
    {
        return ProductImport::create([
            'original_filename' => $filename,
            'type' => $type,
            'status' => ImportState::Completed,
        ]);
    }
}
