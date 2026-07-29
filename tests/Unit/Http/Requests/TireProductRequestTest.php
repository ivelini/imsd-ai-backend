<?php

namespace Tests\Unit\Http\Requests;

use App\Http\Requests\Admin\Catalog\Tire\TireProductRequest;
use PHPUnit\Framework\TestCase;

/** Валидация шины: правила и сообщения. */
class TireProductRequestTest extends TestCase
{
    private TireProductRequest $request;

    protected function setUp(): void
    {
        $this->request = new TireProductRequest;
    }

    public function test_rules_contain_required_fields(): void
    {
        $rules = $this->request->rules();

        $this->assertArrayHasKey('brand_id', $rules);
        $this->assertArrayHasKey('name', $rules);
        $this->assertArrayHasKey('season', $rules);
    }

    public function test_rules_have_brand_exists_validation(): void
    {
        $rules = $this->request->rules();

        $this->assertContains('exists:brands,id', $rules['brand_id']);
    }

    public function test_messages_contain_russian_text(): void
    {
        $messages = $this->request->messages();

        $this->assertSame('Бренд обязателен.', $messages['brand_id.required']);
        $this->assertSame('Название модели обязательно.', $messages['name.required']);
    }
}
