<?php

namespace App\Enums\Import;

/** Статус импорта: жизненный цикл от загрузки до завершения или ошибки. */
enum ImportState: string
{
    case Pending = 'pending';
    case Processing = 'processing';
    case Completed = 'completed';
    case Failed = 'failed';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Ожидание',
            self::Processing => 'В обработке',
            self::Completed => 'Завершён',
            self::Failed => 'Ошибка',
        };
    }
}
