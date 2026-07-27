<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('delivery_points', function (Blueprint $table) {
            $table->id();
            $table->foreignId('city_id')->constrained();
            $table->string('address');
            $table->string('phone', 30)->nullable();
            $table->string('email')->nullable();
            $table->string('work_hours')->nullable();
            $table->text('info')->nullable();
            $table->boolean('pickup_from_truck')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('delivery_points');
    }
};
