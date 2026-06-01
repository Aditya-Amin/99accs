<?php

namespace App\Observers;

use App\Models\Customer;
use App\Support\AdminNotifier;

class CustomerObserver
{
    public function created(Customer $customer): void
    {
        // Skip guest-checkout shells: those are auto-created during checkout and
        // already surface via the "New Order Received" alert, so a separate
        // "new customer" ping would just be noise. A real sign-up has a usable
        // password (is_legacy = false, must_reset_password = false).
        if ($customer->must_reset_password) {
            return;
        }

        $name = trim("{$customer->first_name} {$customer->last_name}") ?: $customer->email;

        AdminNotifier::notify(
            title: 'New Customer Signed Up',
            body: "{$name} ({$customer->email})",
            icon: 'heroicon-o-user-plus',
            iconColor: 'info',
            actionUrl: route('filament.admin.resources.customers.edit', ['record' => $customer->id], absolute: false),
        );
    }
}
