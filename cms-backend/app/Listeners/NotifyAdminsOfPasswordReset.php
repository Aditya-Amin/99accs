<?php

namespace App\Listeners;

use App\Models\Customer;
use App\Support\AdminNotifier;
use Illuminate\Auth\Events\PasswordReset;

/**
 * A password reset isn't a model lifecycle event, so it can't be an Eloquent
 * observer — it's an auth event. PasswordResetController dispatches
 * Illuminate\Auth\Events\PasswordReset on a successful reset; we listen for it
 * and alert the admins (a migrated user finishing setup, or a real reset).
 */
class NotifyAdminsOfPasswordReset
{
    public function handle(PasswordReset $event): void
    {
        $user = $event->user;

        $label = $user instanceof Customer
            ? (trim("{$user->first_name} {$user->last_name}") ?: $user->email)
            : ($user->email ?? 'A user');

        $actionUrl = $user instanceof Customer
            ? route('filament.admin.resources.customers.edit', ['record' => $user->id], absolute: false)
            : null;

        AdminNotifier::notify(
            title: 'Password Reset Completed',
            body: "{$label} ({$user->email}) set a new password.",
            icon: 'heroicon-o-key',
            iconColor: 'warning',
            actionUrl: $actionUrl,
        );
    }
}
