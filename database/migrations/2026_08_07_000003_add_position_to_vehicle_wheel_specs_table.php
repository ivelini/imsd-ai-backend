<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vehicle_wheel_specs', function (Blueprint $table) {
            $table->string('position', 10)->nullable()->after('type');
        });
    }

    public function down(): void
    {
        Schema::table('vehicle_wheel_specs', function (Blueprint $table) {
            $table->dropColumn('position');
        });
    }
};
