<?php

namespace Tests\Unit;

use Tests\TestCase;

/**
 * Для чего: страж против попадания тестов на боевую БД.
 *
 * phpunit.xml переопределяет DB_CONNECTION на sqlite :memory:, но при
 * закешированном config (php artisan config:cache) эти переопределения
 * игнорируются — RefreshDatabase сделает migrate:fresh на pgsql.
 * Тест падает громко и раньше Feature-сьютов (Unit идёт первым).
 */
class DatabaseConnectionGuardTest extends TestCase
{
    public function test_default_connection_is_sqlite(): void
    {
        $this->assertSame('sqlite', config('database.default'));
    }

    public function test_database_is_in_memory(): void
    {
        $this->assertSame(':memory:', config('database.connections.sqlite.database'));
    }
}
