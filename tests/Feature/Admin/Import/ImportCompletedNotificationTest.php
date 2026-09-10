<?php

namespace Tests\Feature\Admin\Import;

use App\Filament\Pages\ImportProducts;
use App\Models\System\ProductImport;
use App\Notifications\Admin\ImportCompletedNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesAdmin;
use Tests\TestCase;

/** Уведомление о завершении импорта: ссылка ведёт на страницу панели, не в мёртвый адрес React-админки. */
class ImportCompletedNotificationTest extends TestCase
{
    use CreatesAdmin, RefreshDatabase;

    public function test_notification_points_to_panel_page(): void
    {
        $import = ProductImport::create([
            'original_filename' => 'tires.xlsx',
            'created_rows' => 10,
            'updated_rows' => 5,
            'failed_rows' => 0,
        ]);

        $data = (new ImportCompletedNotification($import))->toArray($this->createAdmin());

        $this->assertSame(ImportProducts::getUrl(), $data['action_url']);
    }
}
