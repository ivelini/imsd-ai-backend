<?php

namespace Tests\Unit\Services\Catalog;

use App\Enums\Catalog\BrandType;
use App\Services\Catalog\SeoTitleBuilder;
use PHPUnit\Framework\TestCase;

/** Сборка SEO-заголовка листинга — чистые функции без БД. */
class SeoTitleBuilderTest extends TestCase
{
    public function test_category_label_by_type(): void
    {
        $this->assertSame('Шины', SeoTitleBuilder::category(BrandType::Tire));
        $this->assertSame('Диски', SeoTitleBuilder::category(BrandType::Wheel));
        $this->assertSame('Шины и диски', SeoTitleBuilder::category(BrandType::Both));
    }

    public function test_prepositional_city_heuristics(): void
    {
        // Полная «в-фраза»: суффиксные правила предложного падежа
        $this->assertSame('в Челябинске', SeoTitleBuilder::prepositionalCity('Челябинск'));
        $this->assertSame('в Екатеринбурге', SeoTitleBuilder::prepositionalCity('Екатеринбург'));
        $this->assertSame('в Волгограде', SeoTitleBuilder::prepositionalCity('Волгоград'));
    }

    public function test_prepositional_city_fallback_to_city_word(): void
    {
        // Суффикс не покрыт правилами — «в городе {name}», без ошибок склонения
        $this->assertSame('в городе Москва', SeoTitleBuilder::prepositionalCity('Москва'));
    }

    public function test_title_build(): void
    {
        $this->assertSame(
            'Шины Nokian в Челябинске',
            SeoTitleBuilder::title(BrandType::Tire, 'Nokian', 'Челябинск'),
        );
    }
}
