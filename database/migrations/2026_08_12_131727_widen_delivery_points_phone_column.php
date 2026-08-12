<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** Телефон в точке выдачи — до 100 символов (несколько номеров через запятую). */
    public function up(): void
    {
        Schema::table('delivery_points', function (Blueprint $table) {
            $table->string('phone', 100)->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('delivery_points', function (Blueprint $table) {
            $table->string('phone', 30)->nullable()->change();
        });
    }
};
