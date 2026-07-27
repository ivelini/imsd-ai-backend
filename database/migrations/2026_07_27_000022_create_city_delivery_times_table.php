<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('city_delivery_times', function (Blueprint $table) {
            $table->id();
            $table->foreignId('city_id')->constrained();
            $table->unsignedSmallInteger('delivery_days');
            $table->unsignedSmallInteger('priority')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('city_delivery_times');
    }
};
