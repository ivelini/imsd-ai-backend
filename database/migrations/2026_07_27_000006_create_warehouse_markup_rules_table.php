<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('warehouse_markup_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('warehouse_id')->constrained();
            $table->decimal('price_from', 10, 2);
            $table->decimal('price_to', 10, 2);
            $table->decimal('coefficient', 5, 2);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('warehouse_markup_rules');
    }
};
