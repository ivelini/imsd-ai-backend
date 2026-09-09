<?php

namespace App\Filament\Resources;

use App\Filament\Resources\NotificationResource\Pages\ListNotifications;
use App\Filament\Resources\NotificationResource\Tables\NotificationsTable;
use App\Models\Auth\Admin;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Notifications\DatabaseNotification;

/** Уведомления текущего администратора из таблицы notifications. */
class NotificationResource extends Resource
{
    protected static ?string $model = DatabaseNotification::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBell;

    protected static ?string $navigationLabel = 'Уведомления';

    protected static ?string $modelLabel = 'уведомление';

    protected static ?string $pluralModelLabel = 'Уведомления';

    public static function table(Table $table): Table
    {
        return NotificationsTable::configure($table);
    }

    /** @return Builder<DatabaseNotification> */
    public static function getEloquentQuery(): Builder
    {
        /** @var Admin $admin */
        $admin = auth()->user();

        return DatabaseNotification::query()
            ->where('notifiable_type', Admin::class)
            ->where('notifiable_id', $admin->id);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListNotifications::route('/'),
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }
}
