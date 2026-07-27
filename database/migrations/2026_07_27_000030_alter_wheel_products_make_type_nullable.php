<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('wheel_products', function (Blueprint $table) {
            $table->string('type', 20)->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('wheel_products', function (Blueprint $table) {
            $table->string('type', 20)->nullable(false)->change();
        });
    }
};
