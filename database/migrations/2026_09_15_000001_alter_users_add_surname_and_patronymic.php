<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * ФИО клиента по частям: `name` — имя человека, `surname` и `patronymic` — фамилия и отчество.
     * Фамилию и отчество сайт не спрашивает (там имя одной строкой) — колонки nullable,
     * обязательность держит форма панели.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('surname')->nullable();
            $table->string('patronymic')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['surname', 'patronymic']);
        });
    }
};
