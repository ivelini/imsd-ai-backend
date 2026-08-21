<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tire_products', function (Blueprint $table) {
            $table->jsonb('euro_label')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('tire_products', function (Blueprint $table) {
            $table->dropColumn('euro_label');
        });
    }
};
