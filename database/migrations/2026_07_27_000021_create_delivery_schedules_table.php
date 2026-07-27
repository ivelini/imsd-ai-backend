<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('delivery_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('warehouse_id')->constrained();
            $table->unsignedSmallInteger('day_of_week'); // 0=пн … 6=вс
            $table->time('cutoff_time');
            $table->unsignedSmallInteger('days_before');
            $table->unsignedSmallInteger('days_after');
            $table->timestamps();

            $table->unique(['warehouse_id', 'day_of_week']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('delivery_schedules');
    }
};
