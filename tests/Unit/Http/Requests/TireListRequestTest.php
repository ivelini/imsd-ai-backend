<?php

namespace Tests\Unit\Http\Requests;

use App\Http\Requests\Catalog\TireListRequest;
use Tests\TestCase;

/** Тесты валидации TireListRequest. */
class TireListRequestTest extends TestCase
{
    public function test_rules_contains_public_filter_fields(): void
    {
        $request = new TireListRequest;
        $rules = $request->rules();

        foreach (['width', 'profile', 'diameter', 'season', 'studded', 'brand', 'country', 'delivery', 'price_min', 'price_max', 'city_id'] as $field) {
            $this->assertArrayHasKey($field, $rules, "Field '$field' should be present");
        }
        $this->assertContains('exists:brands,slug', $rules['brand']);
        $this->assertContains('exists:countries,slug', $rules['country']);
        $this->assertContains('exists:cities,id', $rules['city_id']);
    }

    public function test_rules_contains_pagination_fields(): void
    {
        $request = new TireListRequest;
        $rules = $request->rules();

        $this->assertContains('integer', $rules['page']);
        $this->assertContains('min:1', $rules['page']);
        $this->assertContains('integer', $rules['per_page']);
        $this->assertContains('min:10', $rules['per_page']);
        $this->assertContains('max:100', $rules['per_page']);
    }

    public function test_rules_contains_sort_fields(): void
    {
        $request = new TireListRequest;
        $rules = $request->rules();

        $sortBy = array_map('strval', $rules['sort_by']);
        $this->assertContains('in:"price"', $sortBy);
        $sortDir = array_map('strval', $rules['sort_dir']);
        $this->assertContains('in:"asc","desc"', $sortDir);
    }

    public function test_all_top_level_fields_are_nullable(): void
    {
        $request = new TireListRequest;
        $rules = $request->rules();

        foreach ($rules as $field => $fieldRules) {
            // Wildcard-правила (width.*) проверяются только когда родительский массив передан.
            if (str_contains($field, '.*')) {
                continue;
            }
            $this->assertContains('nullable', $fieldRules, "Field '$field' should be nullable");
        }
    }
}
