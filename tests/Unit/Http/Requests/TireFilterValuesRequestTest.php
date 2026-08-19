<?php

namespace Tests\Unit\Http\Requests;

use App\Http\Requests\Catalog\TireFilterValuesRequest;
use Tests\TestCase;

/** Тесты валидации TireFilterValuesRequest. */
class TireFilterValuesRequestTest extends TestCase
{
    public function test_rules_contains_nullable_city_id(): void
    {
        $request = new TireFilterValuesRequest;
        $rules = $request->rules();

        $this->assertArrayHasKey('city_id', $rules);
        $this->assertContains('nullable', $rules['city_id']);
        $this->assertContains('exists:cities,id', $rules['city_id']);
    }
}
