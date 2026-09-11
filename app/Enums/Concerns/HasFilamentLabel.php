<?php

namespace App\Enums\Concerns;

/**
 * Метка энума для UI-компонентов Filament: `getLabel()` отдаёт доменный `label()`.
 *
 * Без контракта `HasLabel` Filament молча выводит имя кейса вместо метки
 * (`HasOptions::getOptions()`), поэтому энум объявляет `implements HasLabel` и подключает трейт.
 */
trait HasFilamentLabel
{
    public function getLabel(): string
    {
        return $this->label();
    }
}
