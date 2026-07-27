<?php

namespace App\Providers;

use App\Models\Article;
use App\Models\Catalog\TireProduct;
use App\Models\Catalog\WheelProduct;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Relation::morphMap([
            'tire' => TireProduct::class,
            'wheel' => WheelProduct::class,
            'article' => Article::class,
        ]);
    }
}
