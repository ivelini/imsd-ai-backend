<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** Позиция договора хранения: что именно оставлено и с какими особенностями. */
    public function up(): void
    {
        Schema::create('storage_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('storage_contract_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->text('description')->nullable(); // особенности: комплектность, повреждения, метки
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('storage_items');
    }
};
