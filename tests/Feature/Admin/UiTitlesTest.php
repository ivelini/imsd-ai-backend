<?php

namespace Tests\Feature\Admin;

use Filament\Facades\Filament;
use Tests\TestCase;

/**
 * Русские заголовки страниц панели.
 *
 * Ярлыки сущностей задаются на ресурсах; формулировки страниц создания и правки —
 * подменой строк Filament (lang/vendor/filament-panels) — ключи вендора могут
 * переехать при обновлении, тогда подмена тихо перестанет применяться.
 */
class UiTitlesTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        app()->setLocale('ru');
    }

    public function test_all_resource_titles_are_russian(): void
    {
        foreach (Filament::getPanel('admin')->getResources() as $resource) {
            foreach ([$resource::getTitleCaseModelLabel(), $resource::getTitleCasePluralModelLabel()] as $title) {
                self::assertSame(0, preg_match('/[A-Za-z]/', $title), sprintf(
                    '%s: в заголовке «%s» осталась латиница',
                    class_basename($resource),
                    $title,
                ));
            }
        }
    }

    /** Filament капитализирует каждое слово ярлыка («Наценки На Доставку») — ресурсы гасят это флагом. */
    public function test_resource_titles_are_not_title_cased(): void
    {
        foreach (Filament::getPanel('admin')->getResources() as $resource) {
            foreach ([$resource::getTitleCaseModelLabel(), $resource::getTitleCasePluralModelLabel()] as $title) {
                self::assertSame(1, preg_match_all('/\p{Lu}/u', $title), sprintf(
                    '%s: заголовок «%s» капитализирован по словам',
                    class_basename($resource),
                    $title,
                ));
            }
        }
    }

    public function test_create_and_edit_page_titles_follow_russian_pattern(): void
    {
        self::assertSame(
            'Создание: Бренд',
            __('filament-panels::resources/pages/create-record.title', ['label' => 'Бренд']),
        );

        self::assertSame(
            'Изменение: Бренд',
            __('filament-panels::resources/pages/edit-record.title', ['label' => 'Бренд']),
        );
    }
}
