<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tire_products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('brand_id')->constrained();
            $table->string('name');
            $table->foreignId('supplier_id')->nullable()->constrained();
            $table->foreignId('country_id')->nullable()->constrained();
            $table->string('ean', 50)->nullable()->unique();
            $table->string('season', 20); // winter, summer, all-season
            $table->unsignedSmallInteger('width')->nullable();
            $table->unsignedSmallInteger('profile')->nullable();
            $table->string('diameter', 10)->nullable();
            $table->string('load_index', 10)->nullable();
            $table->string('speed_index', 5)->nullable();
            $table->boolean('is_studded')->default(false);
            $table->boolean('is_runflat')->default(false);
            $table->boolean('is_xl')->default(false);
            $table->unsignedSmallInteger('year')->nullable();
            $table->text('description')->nullable();
            $table->boolean('is_published')->default(true);
            $table->boolean('is_bestseller')->default(false);
            $table->boolean('is_new')->default(false);
            $table->timestamps();

            $table->index(['brand_id', 'season']);
            $table->index(['width', 'profile', 'diameter']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tire_products');
    }
};
