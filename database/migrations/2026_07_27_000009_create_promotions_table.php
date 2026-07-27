<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('promotions', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('type', 20); // percent, fixed, gift, special
            $table->decimal('value', 10, 2)->nullable();
            $table->timestamp('starts_at');
            $table->timestamp('ends_at');
            $table->nullableMorphs('promotable');
            $table->timestamps();

            $table->index(['starts_at', 'ends_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('promotions');
    }
};
