<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('booking_price_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('service_id')->constrained('booking_services')->restrictOnDelete();
            $table->unsignedSmallInteger('radius');
            $table->string('car_type');
            $table->unsignedInteger('price'); // копейки
            $table->timestamps();

            $table->unique(['service_id', 'radius', 'car_type'], 'booking_price_rules_combo_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('booking_price_rules');
    }
};
