<?php

namespace App\Support;

use App\Models\User;
use Filament\Notifications\Actions\Action;
use Filament\Notifications\Notification;

/**
 * Centralises "tell every admin something happened" so the observers,
 * listeners and controllers that raise dashboard alerts all behave the same.
 *
 * Notifications are written to the `notifications` table and surface in the
 * admin panel's notification bell, which the panel polls (see
 * AdminPanelProvider::databaseNotificationsPolling). With Laravel Echo + Reverb
 * configured, Filament can push them instantly instead.
 *
 * IMPORTANT: we send with notifyNow() rather than Filament's sendToDatabase().
 * Filament's DatabaseNotification implements ShouldQueue, so sendToDatabase()
 * QUEUES the write — and with QUEUE_CONNECTION=database and no queue:work
 * running, those sends sit in the `jobs` table forever and the admin never sees
 * them. notifyNow() persists the row immediately, independent of any worker.
 */
class AdminNotifier
{
    public static function notify(
        string $title,
        string $body,
        string $icon = 'heroicon-o-bell',
        string $iconColor = 'primary',
        ?string $actionUrl = null,
        string $actionLabel = 'View',
    ): void {
        $admins = User::all();
        if ($admins->isEmpty()) {
            return;
        }

        $notification = Notification::make()
            ->title($title)
            ->icon($icon)
            ->iconColor($iconColor)
            ->body($body);

        if ($actionUrl !== null) {
            $notification->actions([
                Action::make('view')->label($actionLabel)->button()->url($actionUrl),
            ]);
        }

        // Synchronous write — bypasses the queue so it lands in `notifications`
        // immediately (no queue worker required). Filament's bell polls it up.
        $databaseNotification = $notification->toDatabase();
        $admins->each(fn (User $admin) => $admin->notifyNow($databaseNotification));
    }
}
