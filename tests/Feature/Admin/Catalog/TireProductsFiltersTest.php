<?php

namespace Tests\Feature\Admin\Catalog;

use App\Filament\Clusters\Catalog\Resources\TireProducts\Pages\ListTireProducts;
use App\Models\Catalog\Tire\TireProduct;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\Concerns\CreatesAdmin;
use Tests\TestCase;

/** Фильтры листинга шин: сезон, ширина, профиль, диаметр, шипы. */
class TireProductsFiltersTest extends TestCase
{
    use CreatesAdmin, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAs($this->createAdmin(), 'admin');

        // Тест без HTTP-запроса: middleware панели не выполняется, панель задаётся явно.
        Filament::setCurrentPanel(Filament::getPanel('admin'));
    }

    public function test_season_filter_narrows_table(): void
    {
        $winter = TireProduct::factory()->create(['season' => 'winter']);
        $summer = TireProduct::factory()->create(['season' => 'summer']);

        Livewire::test(ListTireProducts::class)
            ->filterTable('season', 'winter')
            ->assertCanSeeTableRecords([$winter])
            ->assertCanNotSeeTableRecords([$summer]);
    }

    public function test_width_filter_narrows_table(): void
    {
        $wide = TireProduct::factory()->create(['width' => 205]);
        $narrow = TireProduct::factory()->create(['width' => 175]);

        Livewire::test(ListTireProducts::class)
            ->filterTable('width', 205)
            ->assertCanSeeTableRecords([$wide])
            ->assertCanNotSeeTableRecords([$narrow]);
    }

    public function test_profile_filter_narrows_table(): void
    {
        $low = TireProduct::factory()->create(['profile' => 55]);
        $high = TireProduct::factory()->create(['profile' => 65]);

        Livewire::test(ListTireProducts::class)
            ->filterTable('profile', 55)
            ->assertCanSeeTableRecords([$low])
            ->assertCanNotSeeTableRecords([$high]);
    }

    public function test_diameter_filter_narrows_table(): void
    {
        $small = TireProduct::factory()->create(['diameter' => '16']);
        $large = TireProduct::factory()->create(['diameter' => '18']);

        Livewire::test(ListTireProducts::class)
            ->filterTable('diameter', '16')
            ->assertCanSeeTableRecords([$small])
            ->assertCanNotSeeTableRecords([$large]);
    }

    /** Реализация, потерявшая различие значений (фильтр всегда true), проходит тесты 1–4 и падает здесь. */
    public function test_studded_filter_distinguishes_both_values(): void
    {
        $studded = TireProduct::factory()->create(['is_studded' => true]);
        $plain = TireProduct::factory()->create(['is_studded' => false]);

        Livewire::test(ListTireProducts::class)
            ->filterTable('is_studded', 1)
            ->assertCanSeeTableRecords([$studded])
            ->assertCanNotSeeTableRecords([$plain]);

        Livewire::test(ListTireProducts::class)
            ->filterTable('is_studded', 0)
            ->assertCanSeeTableRecords([$plain])
            ->assertCanNotSeeTableRecords([$studded]);
    }

    /** Опции берутся из каталога, а не из справочника размеров: 195 нет ни у одного товара. */
    public function test_dimension_options_come_from_catalog_sorted(): void
    {
        TireProduct::factory()->create(['width' => 205]);
        TireProduct::factory()->create(['width' => 175]);

        $options = Livewire::test(ListTireProducts::class)
            ->instance()
            ->getTable()
            ->getFilter('width')
            ->getOptions();

        self::assertSame([175 => 175, 205 => 205], $options);
    }

    /**
     * Без контракта HasLabel Filament отдаёт имя кейса — в опциях появится Winter.
     *
     * Зимней шины в данных нет намеренно: колонка «Сезон» рендерит ту же label(),
     * поэтому с зимним товаром тест зеленел бы и без фильтра.
     */
    public function test_season_filter_options_use_russian_labels(): void
    {
        TireProduct::factory()->create(['season' => 'summer']);

        Livewire::test(ListTireProducts::class)
            ->assertSee('Сезонность')
            ->assertSee('Зимняя')
            ->assertDontSee('Winter');
    }
}
