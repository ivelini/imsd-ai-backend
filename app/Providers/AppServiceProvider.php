<?php

namespace App\Providers;

use App\Models\Article;
use App\Models\Catalog\Brand;
use App\Models\Catalog\Stock;
use App\Models\Catalog\Supplier;
use App\Models\Catalog\TireProduct;
use App\Models\Catalog\WheelProduct;
use App\Observers\BrandObserver;
use App\Observers\StockObserver;
use App\Observers\SupplierObserver;
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

        Brand::observe(BrandObserver::class);
        Supplier::observe(SupplierObserver::class);
        Stock::observe(StockObserver::class);
    }
}
