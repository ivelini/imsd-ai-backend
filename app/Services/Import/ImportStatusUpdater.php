<?php

namespace App\Services\Import;

use App\Enums\Import\ImportState;
use App\Events\Admin\ImportCompleted;
use App\Models\System\ProductImport;

/** Управление статусами ProductImport: единая точка для всех job'ов (было 5 копий). */
final readonly class ImportStatusUpdater
{
    public function markProcessing(int $importId): void
    {
        ProductImport::where('id', $importId)->update([
            'status' => ImportState::Processing->value,
            'started_at' => now(),
        ]);
    }

    /** @param  array<string, mixed>  $counters */
    public function markCompleted(int $importId, array $counters = []): void
    {
        $data = array_merge($counters, [
            'status' => ImportState::Completed->value,
            'finished_at' => now(),
        ]);
        ProductImport::where('id', $importId)->update($data);

        $import = ProductImport::find($importId);
        if ($import) {
            event(new ImportCompleted($import));
        }
    }

    public function markFailed(int $importId, \Throwable $e, string $messagePrefix = ''): void
    {
        ProductImport::where('id', $importId)->update([
            'status' => ImportState::Failed->value,
            'error_message' => ($messagePrefix !== '' ? $messagePrefix.': ' : '').$e->getMessage(),
            'finished_at' => now(),
        ]);

        $import = ProductImport::find($importId);
        if ($import) {
            event(new ImportCompleted($import));
        }
    }

    /** Атомарный переход processing → completed (для batch-finally). */
    public function completeIfProcessing(int $importId): bool
    {
        $updated = ProductImport::where('id', $importId)
            ->where('status', ImportState::Processing)
            ->update([
                'status' => ImportState::Completed->value,
                'finished_at' => now(),
            ]);

        if ($updated) {
            $import = ProductImport::find($importId);
            if ($import) {
                event(new ImportCompleted($import));
            }

            return true;
        }

        return false;
    }
}
