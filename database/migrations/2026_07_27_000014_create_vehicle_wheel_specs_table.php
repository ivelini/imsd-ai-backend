<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vehicle_wheel_specs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('modification_id')->constrained('vehicle_modifications');
            $table->string('type', 20); // oem, replacement, tuning
            $table->decimal('width', 5, 1)->nullable();
            $table->unsignedSmallInteger('diameter')->nullable();
            $table->decimal('et', 5, 1)->nullable();
            $table->string('pcd', 20)->nullable();
            $table->decimal('hub_diameter', 5, 1)->nullable();
            $table->string('bolts', 20)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vehicle_wheel_specs');
    }
};
