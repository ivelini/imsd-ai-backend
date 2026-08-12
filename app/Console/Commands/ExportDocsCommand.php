<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

/** Экспорт всех OpenAPI-документов (фронт + админка). */
final class ExportDocsCommand extends Command
{
    protected $signature = 'api:export-docs';

    protected $description = 'Экспорт OpenAPI для фронта и админки';

    public function handle(): int
    {
        $this->call('scramble:export');
        $this->call('scramble:export', ['--api' => 'admin']);

        $this->info('OpenAPI документы экспортированы.');

        return self::SUCCESS;
    }
}
