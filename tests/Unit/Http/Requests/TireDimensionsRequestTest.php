<?php

namespace Tests\Unit\Http\Requests;

use App\Http\Requests\Admin\Catalog\Tire\TireDimensionsRequest;
use Tests\TestCase;

/** Тесты валидации TireDimensionsRequest. */
class TireDimensionsRequestTest extends TestCase
{
    public function test_rules_contains_dimension_array_fields(): void
    {
        $request = new TireDimensionsRequest;
        $rules = $request->rules();

        $this->assertArrayHasKey('width', $rules);
        $this->assertArrayHasKey('profile', $rules);
        $this->assertArrayHasKey('diameter', $rules);
        $this->assertArrayHasKey('load_index', $rules);
        $this->assertArrayHasKey('speed_index', $rules);
        $this->assertArrayHasKey('year', $rules);
    }

    public function test_rules_contains_boolean_filter_fields(): void
    {
        $request = new TireDimensionsRequest;
        $rules = $request->rules();

        $this->assertArrayHasKey('is_studded', $rules);
        $this->assertArrayHasKey('is_runflat', $rules);
        $this->assertArrayHasKey('is_xl', $rules);
        $this->assertArrayHasKey('is_bestseller', $rules);
        $this->assertArrayHasKey('is_new', $rules);
        $this->assertArrayHasKey('is_published', $rules);
    }

    public function test_rules_contains_relation_filters(): void
    {
        $request = new TireDimensionsRequest;
        $rules = $request->rules();

        $this->assertArrayHasKey('brand_id', $rules);
        $this->assertArrayHasKey('model_id', $rules);
        $this->assertArrayHasKey('season', $rules);
        $this->assertArrayHasKey('city_id', $rules);
    }

    public function test_rules_does_not_contain_pagination_fields(): void
    {
        $request = new TireDimensionsRequest;
        $rules = $request->rules();

        $this->assertArrayNotHasKey('page', $rules);
        $this->assertArrayNotHasKey('per_page', $rules);
        $this->assertArrayNotHasKey('sort_by', $rules);
    }

    public function test_all_filter_fields_are_nullable(): void
    {
        $request = new TireDimensionsRequest;
        $rules = $request->rules();

        foreach ($rules as $field => $fieldRules) {
            // Wildcard-правила (width.*) не обязаны быть nullable —
            // они проверяются только когда родительский массив передан.
            if (str_contains($field, '.*')) {
                continue;
            }
            $this->assertContains('nullable', $fieldRules, "Field '$field' should be nullable");
        }
    }
}
