<?php

namespace App\Console\Commands;

use App\Actions\Booking\GenerateSlotGrid;
use Illuminate\Console\Command;

class SlotsGenerate extends Command
{
    protected $signature = 'slots:generate';

    protected $description = 'Генерация сетки слотов по шаблону недели на горизонт записи (ADR 0001 tireslot)';

    public function handle(GenerateSlotGrid $generateSlots): int
    {
        $generateSlots->execute();

        $this->info('Сетка слотов обновлена.');

        return self::SUCCESS;
    }
}
