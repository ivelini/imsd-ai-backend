<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // dropForeign по колонке: sqlite не умеет удалять constraint по имени (пересоздаёт таблицу)
        foreach (['tire_products', 'wheel_products'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->dropForeign(['supplier_id']);
                $table->dropColumn('supplier_id');
            });
        }
    }

    public function down(): void
    {
        Schema::table('tire_products', function (Blueprint $table) {
            $table->foreignId('supplier_id')->nullable()->constrained();
        });

        Schema::table('wheel_products', function (Blueprint $table) {
            $table->foreignId('supplier_id')->nullable()->constrained();
        });
    }
};
