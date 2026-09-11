<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('booking_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_id')->constrained()->cascadeOnDelete();
            $table->foreignId('service_id')->constrained('booking_services')->restrictOnDelete(); // история: услугу деактивируют, не удаляют
            $table->unsignedInteger('price'); // цена строки на момент записи, копейки
            $table->unsignedTinyInteger('quantity')->default(1); // 1–4, цена строки — за единицу
            $table->timestamps();

            $table->unique(['booking_id', 'service_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('booking_items');
    }
};
