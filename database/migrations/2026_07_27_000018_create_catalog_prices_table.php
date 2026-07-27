<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('catalog_prices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('stock_id')->constrained();
            $table->foreignId('city_id')->constrained();
            $table->decimal('price', 10, 2);
            $table->unsignedSmallInteger('delivery_min')->nullable();
            $table->unsignedSmallInteger('delivery_max')->nullable();
            $table->timestamps();

            $table->unique(['stock_id', 'city_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('catalog_prices');
    }
};
