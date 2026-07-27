<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vehicle_tire_sizes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('modification_id')->constrained('vehicle_modifications');
            $table->string('type', 20); // oem, replacement, tuning
            $table->unsignedSmallInteger('width');
            $table->unsignedSmallInteger('profile');
            $table->string('diameter', 10);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vehicle_tire_sizes');
    }
};
