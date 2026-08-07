<?php

namespace Tests\Unit\Services\VehicleImport;

use App\Services\VehicleImport\VehicleRowParser;
use Tests\TestCase;

/** Парсинг строк CSV: типоразмеры шин и дисков. */
class VehicleRowParserTest extends TestCase
{
    private VehicleRowParser $parser;

    protected function setUp(): void
    {
        parent::setUp();
        $this->parser = new VehicleRowParser;
    }

    public function test_parse_standard_tire_size(): void
    {
        $sizes = $this->parser->parseTireSizes('235/50 R18', 'stock');

        $this->assertCount(1, $sizes);
        $this->assertEquals(235, $sizes[0]->width);
        $this->assertEquals(50, $sizes[0]->profile);
        $this->assertEquals('18', $sizes[0]->diameter);
        $this->assertEquals('stock', $sizes[0]->type);
        $this->assertNull($sizes[0]->position);
    }

    public function test_parse_standard_wheel_spec(): void
    {
        $specs = $this->parser->parseWheelSpecs('7.5 x 18 ET45', 'stock', '5*114.3', '64.1', '12*1.5');

        $this->assertCount(1, $specs);
        $this->assertEquals(7.5, $specs[0]->width);
        $this->assertEquals(18, $specs[0]->diameter);
        $this->assertEquals(45, $specs[0]->et);
        $this->assertEquals('5*114.3', $specs[0]->pcd);
        $this->assertEquals(64.1, $specs[0]->hubDiameter);
        $this->assertEquals('12*1.5', $specs[0]->bolts);
        $this->assertEquals('stock', $specs[0]->type);
    }

    public function test_parse_throws_on_invalid_tire(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->parser->parseTireSizes('NOT A SIZE', 'stock');
    }

    public function test_parse_throws_on_invalid_wheel(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->parser->parseWheelSpecs('NOT A SPEC', 'stock', '', '', '');
    }

    public function test_parse_empty_string_returns_empty(): void
    {
        $this->assertCount(0, $this->parser->parseTireSizes('', 'stock'));
        $this->assertCount(0, $this->parser->parseWheelSpecs('', 'stock', '', '', ''));
    }

    public function test_parse_staggered_tire_sets_position(): void
    {
        $sizes = $this->parser->parseTireSizes('215/40 R17,245/40 R17', 'stock');

        $this->assertCount(2, $sizes);
        $this->assertEquals('Front', $sizes[0]->position->value);
        $this->assertEquals('17', $sizes[0]->diameter);
        $this->assertEquals('Rear', $sizes[1]->position->value);
        $this->assertEquals('17', $sizes[1]->diameter);
        $this->assertEquals('stock', $sizes[0]->type);
        $this->assertEquals('stock', $sizes[1]->type);
    }

    public function test_parse_staggered_wheel_sets_position(): void
    {
        $specs = $this->parser->parseWheelSpecs('7.5 x 17 ET40,9 x 17 ET30', 'stock', '5*114.3', '70.1', '12*1.5');

        $this->assertCount(2, $specs);
        $this->assertEquals('Front', $specs[0]->position->value);
        $this->assertEquals(7.5, $specs[0]->width);
        $this->assertEquals('Rear', $specs[1]->position->value);
        $this->assertEquals(9, $specs[1]->width);
        $this->assertEquals('5*114.3', $specs[0]->pcd);
        $this->assertEquals('5*114.3', $specs[1]->pcd);
    }

    public function test_parse_alternatives(): void
    {
        $sizes = $this->parser->parseTireSizes('215/50 R17|225/45 R18', 'stock');

        $this->assertCount(2, $sizes);
        $this->assertEquals(215, $sizes[0]->width);
        $this->assertEquals(225, $sizes[1]->width);
        $this->assertEquals('stock', $sizes[0]->type);
        $this->assertEquals('stock', $sizes[1]->type);
    }

    public function test_parse_alternatives_with_staggered(): void
    {
        $sizes = $this->parser->parseTireSizes(
            '215/40 R17,245/40 R17|235/35 R18,255/35 R18',
            'stock',
        );

        $this->assertCount(4, $sizes);
        $this->assertEquals('Front', $sizes[0]->position->value);
        $this->assertEquals('Rear', $sizes[1]->position->value);
        $this->assertEquals('Front', $sizes[2]->position->value);
        $this->assertEquals('Rear', $sizes[3]->position->value);
    }
}
