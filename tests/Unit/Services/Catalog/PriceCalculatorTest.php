<?php

namespace Tests\Unit\Services\Catalog;

use App\Services\Catalog\PriceCalculator;
use Illuminate\Support\Collection;
use PHPUnit\Framework\TestCase;

/** Расчёт финальной цены по предзагруженной коллекции правил наценки. */
class PriceCalculatorTest extends TestCase
{
    private PriceCalculator $calculator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->calculator = new PriceCalculator;
    }

    public function test_calculate_final_price_applies_coefficient(): void
    {
        $allRules = $this->rules([
            'warehouse_id' => 1, 'price_from' => 0, 'price_to' => 200, 'coefficient' => 1.5,
        ]);

        $result = $this->calculator->calculateFinalPrice(100.0, 1, $allRules);

        $this->assertSame(150.0, $result);
    }

    public function test_calculate_returns_purchase_price_when_no_rule(): void
    {
        $allRules = $this->rules([
            'warehouse_id' => 1, 'price_from' => 300, 'price_to' => 500, 'coefficient' => 1.5,
        ]);

        $result = $this->calculator->calculateFinalPrice(100.0, 1, $allRules);

        $this->assertSame(100.0, $result);
    }

    public function test_calculate_prefers_rule_with_smallest_price_from(): void
    {
        $allRules = $this->rules(
            ['warehouse_id' => 1, 'price_from' => 100, 'price_to' => 500, 'coefficient' => 1.2],
            ['warehouse_id' => 1, 'price_from' => 50, 'price_to' => 600, 'coefficient' => 1.5],
        );

        $result = $this->calculator->calculateFinalPrice(200.0, 1, $allRules);

        $this->assertSame(300.0, $result);
    }

    public function test_calculate_ignores_other_warehouse_rules(): void
    {
        $allRules = $this->rules([
            'warehouse_id' => 2, 'price_from' => 0, 'price_to' => 500, 'coefficient' => 1.5,
        ]);

        $result = $this->calculator->calculateFinalPrice(100.0, 1, $allRules);

        $this->assertSame(100.0, $result);
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
