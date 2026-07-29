<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('delivery_point_coefficients');
    }

    public function down(): void
    {
        // Не восстанавливаем — таблица мертва.
    }
};
