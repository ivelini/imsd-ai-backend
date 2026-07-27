<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wheel_products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('brand_id')->constrained();
            $table->string('name');
            $table->foreignId('supplier_id')->nullable()->constrained();
            $table->foreignId('country_id')->nullable()->constrained();
            $table->string('ean', 50)->nullable()->unique();
            $table->string('type', 20); // alloy, steel, forged
            $table->string('color', 50)->nullable();
            $table->string('pcd', 20)->nullable();
            $table->decimal('et', 5, 1)->nullable();
            $table->decimal('hub_diameter', 5, 1)->nullable();
            $table->decimal('width', 5, 1)->nullable();
            $table->unsignedSmallInteger('diameter')->nullable();
            $table->text('description')->nullable();
            $table->boolean('is_published')->default(true);
            $table->boolean('is_bestseller')->default(false);
            $table->boolean('is_new')->default(false);
            $table->timestamps();

            $table->index(['brand_id', 'type']);
            $table->index(['width', 'diameter', 'pcd']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wheel_products');
    }
};
