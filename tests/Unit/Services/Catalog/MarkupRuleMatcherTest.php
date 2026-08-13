<?php

namespace Tests\Unit\Services\Catalog;

use App\Services\Catalog\MarkupRuleMatcher;
use PHPUnit\Framework\TestCase;

/** Чистый матчинг правила наценки по цене — без БД. */
class MarkupRuleMatcherTest extends TestCase
{
    public function test_match_returns_covering_rule(): void
    {
        $rules = [
            ['price_from' => 0, 'price_to' => 200, 'coefficient' => 1.5],
            ['price_from' => 201, 'price_to' => 500, 'coefficient' => 1.3],
        ];

        $rule = MarkupRuleMatcher::match(100.0, $rules);

        $this->assertSame(['price_from' => 0, 'price_to' => 200, 'coefficient' => 1.5], $rule);
    }

    public function test_match_returns_null_when_price_outside_ranges(): void
    {
        $rules = [
            ['price_from' => 300, 'price_to' => 500, 'coefficient' => 1.5],
        ];

        $this->assertNull(MarkupRuleMatcher::match(100.0, $rules));
    }

    public function test_match_prefers_smallest_price_from_regardless_of_input_order(): void
    {
        $rules = [
            ['price_from' => 100, 'price_to' => 500, 'coefficient' => 1.2],
            ['price_from' => 50, 'price_to' => 600, 'coefficient' => 1.5],
        ];

        $rule = MarkupRuleMatcher::match(200.0, array_reverse($rules));

        $this->assertSame(1.5, $rule['coefficient']);
    }

    public function test_match_returns_null_on_empty_rules(): void
    {
        $this->assertNull(MarkupRuleMatcher::match(100.0, []));
    }

    public function test_match_returns_city_markup_rule(): void
    {
        $rules = [
            ['price_from' => 0, 'price_to' => 200, 'markup' => 300],
            ['price_from' => 201, 'price_to' => 500, 'markup' => 500],
        ];

        $rule = MarkupRuleMatcher::match(100.0, $rules);

        $this->assertSame(['price_from' => 0, 'price_to' => 200, 'markup' => 300], $rule);
    }
}
