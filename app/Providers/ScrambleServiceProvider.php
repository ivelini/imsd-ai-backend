<?php

namespace App\Providers;

use Dedoc\Scramble\Scramble;
use Illuminate\Support\ServiceProvider;

class ScrambleServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Scramble::registerApi('admin', [
            'api_path' => 'api/admin',
            'export_path' => 'documentations/scramble/admin-api.json',
            'info' => [
                'description' => 'API админ-панели: каталог, импорт, справочники, гео.',
            ],
            'ui' => [
                'title' => 'IMS API — Админка',
            ],
        ]);
    }
}
