<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stocks', function (Blueprint $table) {
            $table->id();
            $table->morphs('stockable');
            $table->foreignId('warehouse_id')->constrained();
            $table->unsignedInteger('quantity')->default(0);
            $table->decimal('purchase_price', 10, 2)->nullable();
            $table->decimal('price', 10, 2)->nullable();
            $table->timestamps();

            $table->unique(['stockable_type', 'stockable_id', 'warehouse_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stocks');
    }
};
