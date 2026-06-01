<?php

namespace App\Observers;

use App\Models\Order;
use App\Support\AdminNotifier;

class OrderObserver
{
    public function created(Order $order): void
    {
        $customerEmail = $order->customer?->email ?? 'Guest';
        $orderNumber   = $order->number ?? '#' . $order->id;
        $total         = '$' . number_format((float) $order->total_price, 2);

        AdminNotifier::notify(
            title: 'New Order Received',
            body: "{$orderNumber} · {$total} from {$customerEmail}",
            icon: 'heroicon-o-shopping-bag',
            iconColor: 'success',
            actionUrl: route('filament.admin.resources.orders.edit', ['record' => $order->id], absolute: false),
        );
    }

    public function updated(Order $order): void
    {
        // Notify once when payment flips to paid (transaction completed).
        if (! $order->wasChanged('payment_status') || $order->payment_status !== 'paid') {
            return;
        }

        $orderNumber   = $order->number ?? '#' . $order->id;
        $total         = '$' . number_format((float) $order->total_price, 2);
        $customerEmail = $order->customer?->email ?? 'Guest';

        AdminNotifier::notify(
            title: 'Payment Received',
            body: "{$orderNumber} · {$total} paid by {$customerEmail}",
            icon: 'heroicon-o-banknotes',
            iconColor: 'success',
            actionUrl: route('filament.admin.resources.orders.edit', ['record' => $order->id], absolute: false),
        );
    }
}
