<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // nullable: существующие строки без slug (заполнятся при следующем сохранении)
        Schema::table('wheel_products', function (Blueprint $table) {
            $table->string('slug')->nullable()->unique();
        });
    }

    public function down(): void
    {
        Schema::table('wheel_products', function (Blueprint $table) {
            $table->dropUnique(['slug']);
            $table->dropColumn('slug');
        });
    }
};
