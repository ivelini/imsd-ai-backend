<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Нумерация договоров хранения начинается со 100: номер — производная от ID (аксессор number),
 * поэтому первый договор получает id = 100 и печатается как «00100».
 *
 * Внимание: последовательность перезапускается, а не сдвигается за существующие строки — база
 * пересоздаётся (migrate:fresh). На базе с уже заведёнными договорами (id ≥ 100) следующая вставка
 * упадёт на дубле ключа.
 */
return new class extends Migration
{
    public function up(): void
    {
        $connection = DB::connection();

        match ($connection->getDriverName()) {
            'pgsql' => $connection->statement('ALTER SEQUENCE storage_contracts_id_seq RESTART WITH 100'),
            // Тесты идут на sqlite в памяти: своего объекта последовательности там нет — счётчик таблицы
            'sqlite' => $connection->table('sqlite_sequence')
                ->updateOrInsert(['name' => 'storage_contracts'], ['seq' => 99]),
            default => throw new RuntimeException('Нумерация со 100 поддержана только для pgsql и sqlite'),
        };
    }

    public function down(): void
    {
        $connection = DB::connection();

        match ($connection->getDriverName()) {
            'pgsql' => $connection->statement('ALTER SEQUENCE storage_contracts_id_seq RESTART WITH 1'),
            'sqlite' => $connection->table('sqlite_sequence')
                ->updateOrInsert(['name' => 'storage_contracts'], ['seq' => 0]),
            default => throw new RuntimeException('Нумерация со 100 поддержана только для pgsql и sqlite'),
        };
    }
};
