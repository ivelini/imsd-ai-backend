<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::rename('tire_imports', 'product_imports');
    }

    public function down(): void
    {
        Schema::rename('product_imports', 'tire_imports');
    }
};
