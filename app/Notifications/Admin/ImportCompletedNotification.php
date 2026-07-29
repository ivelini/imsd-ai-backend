<?php

namespace App\Notifications\Admin;

use App\Models\System\ProductImport;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

/** Уведомление о завершении импорта товаров. */
class ImportCompletedNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly ProductImport $import,
    ) {}

    /**
     * @return string[]
     */
    public function via(object $notifiable): array
    {
        return ['database', 'broadcast'];
    }

    /** @return array<string, mixed> */
    public function toArray(object $notifiable): array
    {
        $import = $this->import;
        $okCount = $import->created_rows + $import->updated_rows;
        $errorCount = $import->failed_rows;

        if ($errorCount === 0) {
            $icon = 'success';
        } elseif ($okCount === 0) {
            $icon = 'error';
        } else {
            $icon = 'warning';
        }

        return [
            'title' => 'Импорт завершён',
            'body' => sprintf(
                'Файл «%s» загружен: %d товаров, %d ошибок',
                $import->original_filename,
                $okCount,
                $errorCount,
            ),
            'icon' => $icon,
            'action_url' => sprintf('/admin/imports/%d', $import->id),
            'type' => 'import.completed',
        ];
    }
}
