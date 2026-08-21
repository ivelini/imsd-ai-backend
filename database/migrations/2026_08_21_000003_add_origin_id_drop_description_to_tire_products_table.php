<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tire_products', function (Blueprint $table) {
            $table->foreignId('origin_id')
                ->nullable()
                ->after('model_id')
                ->constrained('product_origins')
                ->nullOnDelete();
            $table->dropColumn('description');
        });
    }

    public function down(): void
    {
        Schema::table('tire_products', function (Blueprint $table) {
            $table->text('description')->nullable();
            $table->dropConstrainedForeignId('origin_id');
        });
    }
};
