<?php

namespace Tests\Unit\Enums;

use App\Enums\Catalog\BrandType;
use App\Enums\Catalog\Season;
use App\Enums\Catalog\WheelType;
use App\Enums\Promotion\PromotionType;
use Filament\Support\Contracts\HasLabel;
use PHPUnit\Framework\TestCase;

/**
 * Энумы, выводимые в панели Filament.
 *
 * Без контракта HasLabel Filament игнорирует label() и подставляет имя кейса
 * (Winter вместо «Зимняя») — см. HasOptions::getOptions().
 */
class UiEnumLabelsTest extends TestCase
{
    public function test_ui_enums_return_russian_labels(): void
    {
        self::assertInstanceOf(HasLabel::class, Season::Winter);
        self::assertSame('Зимняя', Season::Winter->getLabel());
        self::assertSame('Всесезонная', Season::AllSeason->getLabel());

        self::assertInstanceOf(HasLabel::class, BrandType::Tire);
        self::assertSame('Шинные', BrandType::Tire->getLabel());

        self::assertInstanceOf(HasLabel::class, WheelType::Alloy);
        self::assertSame('Литые', WheelType::Alloy->getLabel());

        self::assertInstanceOf(HasLabel::class, PromotionType::Percent);
        self::assertSame('Процент', PromotionType::Percent->getLabel());
    }
}
