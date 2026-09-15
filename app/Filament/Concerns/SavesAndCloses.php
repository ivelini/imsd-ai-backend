<?php

namespace App\Filament\Concerns;

use Filament\Actions\Action;

/**
 * Кнопка «Сохранить и закрыть» на страницах правки: сохраняет запись и уводит на экран,
 * откуда оператор пришёл (адрес входа помнит сам Filament — `$previousUrl`, как у кнопки «Отмена»).
 *
 * Возврат делает штатный `save()` через `getRedirectUrl()`, а не наш код после него: при ошибке
 * валидации и при `halt()` (DomainException из Action — например «на это время уже есть запись»)
 * `save()` выходит раньше редиректа, и оператор остаётся на форме с сообщением об ошибке.
 *
 * Адрес возврата страница может сузить через `getReturnUrl()` — если экран входа уводит по кругу
 * (см. `EditSlot`).
 */
trait SavesAndCloses
{
    protected bool $closeAfterSave = false;

    /** Сохранить и вернуться на экран входа — действие кнопки «Сохранить и закрыть». */
    public function saveAndClose(): void
    {
        $this->closeAfterSave = true;

        $this->save();
    }

    /** @return array<int, Action> */
    protected function getFormActions(): array
    {
        return [
            $this->getSaveFormAction(),
            $this->getSaveAndCloseFormAction(),
            $this->getCancelFormAction(),
        ];
    }

    protected function getRedirectUrl(): ?string
    {
        if (! $this->closeAfterSave) {
            return parent::getRedirectUrl();
        }

        return $this->getReturnUrl();
    }

    /** Адрес возврата «Сохранить и закрыть»: экран входа, запасной — список раздела. */
    protected function getReturnUrl(): string
    {
        return $this->previousUrl ?? static::getResource()::getUrl('index');
    }

    protected function getSaveAndCloseFormAction(): Action
    {
        return Action::make('saveAndClose')
            ->label('Сохранить и закрыть')
            ->color('gray')
            ->action('saveAndClose');
    }
}
