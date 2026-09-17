<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** Договор хранения колёс клиента: срок, стоимость и состояние выдачи. */
    public function up(): void
    {
        Schema::create('storage_contracts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->date('starts_on');
            $table->date('ends_on');
            $table->unsignedInteger('price'); // стоимость за весь срок, копейки
            $table->string('status')->default('active');
            $table->timestamp('closed_at')->nullable(); // фактическая выдача колёс
            $table->foreignId('operator_id')->nullable()->constrained('admins')->nullOnDelete();
            $table->timestamps();

            $table->index('status');
            $table->index('ends_on');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('storage_contracts');
    }
};
