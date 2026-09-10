<?php

namespace Tests\Unit\Services\Catalog;

use App\Services\Catalog\PriceCalculator;
use PHPUnit\Framework\TestCase;

/** Цена продажи по правилу наценки склада. */
class PriceCalculatorTest extends TestCase
{
    private PriceCalculator $calculator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->calculator = new PriceCalculator;
    }

    public function test_apply_rule_uses_coefficient_from_array_rule(): void
    {
        $rule = ['price_from' => 0, 'price_to' => 500, 'coefficient' => 1.5];

        $result = $this->calculator->applyRule(100.0, $rule);

        $this->assertSame(150.0, $result);
    }

    public function test_apply_rule_returns_purchase_price_when_no_rule(): void
    {
        $this->assertSame(100.0, $this->calculator->applyRule(100.0, null));
    }

    /** @param  array<int, array<string, int|float>>  $rules */
    private function rules(array ...$rules): Collection
    {
        return collect($rules)
            ->groupBy('warehouse_id')
            ->map(fn (Collection $group) => $group->map(fn (array $r) => [
                'price_from' => $r['price_from'],
                'price_to' => $r['price_to'],
                'coefficient' => $r['coefficient'],
            ])->values());
    }
}
