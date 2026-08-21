<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_origins', function (Blueprint $table) {
            $table->id();
            $table->jsonb('vendor')->nullable();
            $table->jsonb('manufacture_country')->nullable();
            $table->jsonb('manufacture_year')->nullable();
            $table->unique(['vendor', 'manufacture_country', 'manufacture_year']);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_origins');
    }
};
