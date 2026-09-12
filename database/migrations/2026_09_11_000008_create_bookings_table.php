<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bookings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->foreignId('slot_id')->constrained('booking_slots')->restrictOnDelete();
            $table->foreignId('booking_code_id')->nullable()->constrained()->nullOnDelete()->index(); // заявка сайта, подтвердившая запись (верификатор отмены)
            $table->time('start_time');
            $table->string('status')->default('confirmed');
            $table->string('source')->default('site');
            $table->string('cancel_reason')->nullable();
            $table->uuid('idempotency_key')->nullable()->unique();
            // снимок параметров и цены на момент создания (ADR 0017)
            $table->unsignedSmallInteger('radius');
            $table->string('car_type');
            $table->string('plate')->nullable(); // госномер из заявки (снимок)
            $table->unsignedInteger('total_price'); // копейки
            $table->foreignId('operator_id')->nullable()->constrained('admins')->nullOnDelete();
            $table->timestamps();

            $table->index('slot_id');
            $table->index('user_id');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bookings');
    }
};
