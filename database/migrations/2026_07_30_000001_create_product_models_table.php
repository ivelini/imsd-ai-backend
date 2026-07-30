<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_models', function (Blueprint $table) {
            $table->id();
            $table->foreignId('brand_id')->constrained();
            $table->string('name');
            $table->string('slug');
            $table->text('description')->nullable();
            $table->string('image')->nullable();
            $table->string('type', 10); // tire, wheel
            $table->timestamps();

            $table->unique(['brand_id', 'slug']);
            $table->index('type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_models');
    }
};
