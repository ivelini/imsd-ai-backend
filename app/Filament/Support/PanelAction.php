<?php

namespace App\Filament\Support;

use Closure;
use DomainException;
use Filament\Notifications\Notification;

/**
 * Выполнение панельного действия с отчётом пользователю.
 *
 * Preconditions в домене бросают DomainException — в HTTP-слое она превращается в 409,
 * в панели показывается danger-нотификацией.
 */
final class PanelAction
{
    public static function run(string $successTitle, Closure $action): void
    {
        try {
            $action();
            Notification::make()->success()->title($successTitle)->send();
        } catch (DomainException $e) {
            Notification::make()->danger()->title($e->getMessage())->send();
        }
    }
}
