<?php

namespace Tests\Feature\Admin\Catalog;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/** Контракт-отрицание: сущность supplier удалена из схемы. */
class SupplierRemovedTest extends TestCase
{
    use RefreshDatabase;

    public function test_supplier_columns_dropped(): void
    {
        $this->assertFalse(Schema::hasColumn('tire_products', 'supplier_id'));
        $this->assertFalse(Schema::hasColumn('wheel_products', 'supplier_id'));
        $this->assertFalse(Schema::hasTable('suppliers'));
    }
}
