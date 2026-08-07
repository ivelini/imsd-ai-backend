<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vehicle_modifications', function (Blueprint $table) {
            $table->smallInteger('year')->nullable()->after('name');
        });
    }

    public function down(): void
    {
        Schema::table('vehicle_modifications', function (Blueprint $table) {
            $table->dropColumn('year');
        });
    }
};
