<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('catalog_prices', function (Blueprint $table) {
            // Цена без скидки: price = базовая − скидка активной акции (ADR 0010)
            $table->decimal('base_price', 10, 2)->nullable()->after('price');
        });

        // Существующие строки рассчитаны без акций — базовая цена равна текущей
        DB::table('catalog_prices')->update(['base_price' => DB::raw('price')]);
    }

    public function down(): void
    {
        Schema::table('catalog_prices', function (Blueprint $table) {
            $table->dropColumn('base_price');
        });
    }
};
