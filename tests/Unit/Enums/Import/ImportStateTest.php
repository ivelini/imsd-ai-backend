<?php

namespace Tests\Unit\Enums\Import;

use App\Enums\Import\ImportState;
use PHPUnit\Framework\TestCase;

/** Значения статусов импорта совпадают со значениями в БД. */
class ImportStateTest extends TestCase
{
    public function test_values_match_database_strings(): void
    {
        $this->assertSame('pending', ImportState::Pending->value);
        $this->assertSame('processing', ImportState::Processing->value);
        $this->assertSame('completed', ImportState::Completed->value);
        $this->assertSame('failed', ImportState::Failed->value);
    }
}
