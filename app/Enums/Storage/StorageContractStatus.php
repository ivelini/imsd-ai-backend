<?php

namespace App\Enums\Storage;

use App\Enums\Concerns\HasFilamentLabel;
use Filament\Support\Contracts\HasLabel;

/** Состояние договора хранения: колёса лежат на складе или выданы клиенту. */
enum StorageContractStatus: string implements HasLabel
{
    use HasFilamentLabel;

    case Active = 'active';
    case Closed = 'closed';

    public function label(): string
    {
        return match ($this) {
            self::Active => 'Действует',
            self::Closed => 'Закрыт',
        };
    }
}
