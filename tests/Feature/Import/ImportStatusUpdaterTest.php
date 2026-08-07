<?php

namespace Tests\Feature\Import;

use App\Enums\Import\ImportState;
use App\Enums\Import\ImportType;
use App\Events\Admin\ImportCompleted;
use App\Models\System\ProductImport;
use App\Services\Import\ImportStatusUpdater;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

/** Жизненный цикл статусов импорта: processing → completed/failed. */
class ImportStatusUpdaterTest extends TestCase
{
    use RefreshDatabase;

    private ImportStatusUpdater $updater;

    private ProductImport $import;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow(now());
        $this->updater = new ImportStatusUpdater;
        $this->import = ProductImport::create([
            'original_filename' => 'test.xlsx',
            'type' => ImportType::Tire->value,
            'status' => ImportState::Pending->value,
        ]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_mark_completed_sets_status_and_fires_event(): void
    {
        Event::fake();

        $this->updater->markCompleted($this->import->id);

        $import = $this->import->refresh();
        $this->assertSame(ImportState::Completed, $import->status);
        $this->assertNotNull($import->finished_at);
        Event::assertDispatched(ImportCompleted::class);
    }

    public function test_complete_if_processing_is_atomic(): void
    {
        Event::fake();

        // перевод в processing → completed
        $this->import->update(['status' => ImportState::Processing->value]);
        $result = $this->updater->completeIfProcessing($this->import->id);

        $this->assertTrue($result);
        $this->assertSame(ImportState::Completed, $this->import->refresh()->status);
        Event::assertDispatched(ImportCompleted::class, 1);

        // повторный вызов — уже completed, переход не должен сработать
        Event::fake();
        $result = $this->updater->completeIfProcessing($this->import->id);

        $this->assertFalse($result);
        Event::assertNotDispatched(ImportCompleted::class);
    }
}
