<?php

namespace Tests\Unit\Models\Storage;

use App\Models\Storage\StorageContract;
use PHPUnit\Framework\TestCase;

/** Номер договора хранения: ID с ведущими нулями — один и тот же в списке, карточке и на бумаге. */
class StorageContractTest extends TestCase
{
    public function test_number_pads_id_to_five_digits(): void
    {
        $this->assertSame('00007', $this->contract(7)->number);
    }

    /** Длинный ID не обрезается: добивка нулями, а не фиксированная ширина */
    public function test_number_keeps_long_id_unchanged(): void
    {
        $this->assertSame('123456', $this->contract(123456)->number);
    }

    private function contract(int $id): StorageContract
    {
        $contract = new StorageContract;
        $contract->id = $id;

        return $contract;
    }
}
