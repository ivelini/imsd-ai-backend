<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Готовые комплексы услуг (например, «Сезонный шиномонтаж»): справочник без собственной
     * цены — клик по комплексу отмечает входящие услуги (×4). В запись/снимок комплекс
     * не попадает: хранятся только услуги с количествами.
     */
    public function up(): void
    {
        Schema::create('booking_complex_services', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('booking_complex_service_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('complex_service_id')->constrained('booking_complex_services')->cascadeOnDelete();
            $table->foreignId('service_id')->constrained('booking_services')->restrictOnDelete(); // услугу деактивируют, не удаляют
            $table->timestamps();

            $table->unique(['complex_service_id', 'service_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('booking_complex_service_items');
        Schema::dropIfExists('booking_complex_services');
    }
};
