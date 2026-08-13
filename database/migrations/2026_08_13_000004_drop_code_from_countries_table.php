<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // SQLite не умеет дропать колонку с уникальным индексом — сначала индекс
        Schema::table('countries', function (Blueprint $table) {
            $table->dropUnique('countries_code_unique');
        });

        Schema::table('countries', function (Blueprint $table) {
            $table->dropColumn('code');
        });
    }

    public function down(): void
    {
        Schema::table('countries', function (Blueprint $table) {
            $table->string('code', 2)->unique();
        });
    }
};
