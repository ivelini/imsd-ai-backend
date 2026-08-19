<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('suppliers');
    }

    public function down(): void
    {
        // Справочник без ссылок намеренно не восстанавливаем — повторное добавление
        // поставщиков будет отдельной фичей с новой схемой.
    }
};
