<?php

namespace Tests\Unit\Services\Import;

use App\Services\Import\OriginParser;
use PHPUnit\Framework\TestCase;

/** Парсинг «##Badge## …» из XLSX-колонок origin_* — чистая функция без БД. */
class OriginParserTest extends TestCase
{
    public function test_parse_full_value(): void
    {
        $origin = OriginParser::parse('##100% Китай## <p>Данные о стране.</p>');

        $this->assertNotNull($origin);
        $this->assertSame('100% Китай', $origin->badge);
        $this->assertSame('<p>Данные о стране.</p>', $origin->description);
    }

    public function test_parse_without_description(): void
    {
        $origin = OriginParser::parse('##Shandong Haohua Tire##');

        $this->assertNotNull($origin);
        $this->assertSame('Shandong Haohua Tire', $origin->badge);
        $this->assertNull($origin->description);
    }

    public function test_parse_invalid_returns_null(): void
    {
        $this->assertNull(OriginParser::parse(''));
        $this->assertNull(OriginParser::parse('просто текст'));
        $this->assertNull(OriginParser::parse('##без закрытия'));
        $this->assertNull(OriginParser::parse(null));
    }
}
