<?php

namespace App\Console\Commands;

use App\Actions\Promotion\RecalculatePromotedPrices;
use Illuminate\Console\Command;

/** Пересчёт цен товаров с акциями: закрывает границы действия акций по расписанию. */
final class SyncPromotionPricesCommand extends Command
{
    protected $signature = 'promotions:sync';

    protected $description = 'Пересчитать цены товаров, участвующих в акциях';

    public function handle(RecalculatePromotedPrices $recalculate): int
    {
        $recalculate->forAnyPromotedProducts();

        $this->info('Цены акционных товаров пересчитаны.');

        return self::SUCCESS;
    }
}
