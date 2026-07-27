<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('delivery_point_coefficients', function (Blueprint $table) {
            $table->id();
            $table->decimal('price_from', 10, 2);
            $table->decimal('price_to', 10, 2);
            $table->string('product_type', 20)->nullable(); // tire, wheel
            $table->decimal('coefficient', 5, 2);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('delivery_point_coefficients');
    }
};
