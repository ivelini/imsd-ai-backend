<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Import;

use App\Services\Import\RowAssembler;
use OpenSpout\Common\Entity\Cell;
use OpenSpout\Common\Entity\Row;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class RowAssemblerTest extends TestCase
{
    private RowAssembler $assembler;

    protected function setUp(): void
    {
        parent::setUp();
        $this->assembler = new RowAssembler;
    }

    #[Test]
    public function test_extract_headers_trims_cells(): void
    {
        $row = new Row([
            Cell::fromValue('code'),
            Cell::fromValue(' name '),
            Cell::fromValue('city_name'),
        ]);

        $result = $this->assembler->extractHeaders($row);

        $this->assertSame(['code', 'name', 'city_name'], $result);
    }

    #[Test]
    public function test_extract_headers_includes_empty_strings(): void
    {
        $row = new Row([
            Cell::fromValue('code'),
            Cell::fromValue(''),
            Cell::fromValue('city_name'),
        ]);

        $result = $this->assembler->extractHeaders($row);

        $this->assertSame(['code', '', 'city_name'], $result);
    }

    #[Test]
    public function test_ensure_required_columns_passes_when_all_present(): void
    {
        $columns = ['code', 'region_name', 'city_name'];
        $required = ['code', 'city_name'];

        $this->assembler->ensureRequiredColumns($columns, $required);

        $this->assertTrue(true); // Не бросило исключение
    }

    #[Test]
    public function test_ensure_required_columns_throws_on_missing(): void
    {
        $columns = ['code', 'name'];
        $required = ['code', 'region_name'];

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Отсутствуют обязательные колонки: region_name');

        $this->assembler->ensureRequiredColumns($columns, $required);
    }

    #[Test]
    public function test_to_assoc_handles_row_shorter_than_header(): void
    {
        $columns = ['a', 'b', 'c'];
        $row = new Row([
            Cell::fromValue('1'),
            Cell::fromValue('2'),
        ]);
        $columnMap = [];

        $result = $this->assembler->toAssoc($columns, $row, $columnMap);

        $this->assertSame(['a' => '1', 'b' => '2', 'c' => null], $result);
    }

    #[Test]
    public function test_to_assoc_applies_column_map(): void
    {
        $columns = ['col_a', 'col_b'];
        $row = new Row([
            Cell::fromValue('x'),
            Cell::fromValue('y'),
        ]);
        $columnMap = ['col_a' => 'mapped'];

        $result = $this->assembler->toAssoc($columns, $row, $columnMap);

        $this->assertSame(['mapped' => 'x', 'col_b' => 'y'], $result);
    }

    #[Test]
    public function test_to_assoc_converts_null_cell_to_null(): void
    {
        $columns = ['a'];
        $row = new Row([
            Cell::fromValue(null),
        ]);
        $columnMap = [];

        $result = $this->assembler->toAssoc($columns, $row, $columnMap);

        $this->assertSame(['a' => null], $result);
    }

    #[Test]
    public function test_to_assoc_empty_cell_string_kept_as_is(): void
    {
        $columns = ['a'];
        $row = new Row([
            Cell::fromValue(''),
        ]);
        $columnMap = [];

        $result = $this->assembler->toAssoc($columns, $row, $columnMap);

        $this->assertSame(['a' => ''], $result);
    }
}
