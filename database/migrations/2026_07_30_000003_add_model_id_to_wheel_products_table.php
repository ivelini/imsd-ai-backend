<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('wheel_products', function (Blueprint $table) {
            $table->foreignId('model_id')->nullable()->after('brand_id')->constrained('product_models');
            $table->index('model_id');
        });
    }

    public function down(): void
    {
        Schema::table('wheel_products', function (Blueprint $table) {
            $table->dropForeign(['model_id']);
            $table->dropIndex(['model_id']);
            $table->dropColumn('model_id');
        });
    }
};
