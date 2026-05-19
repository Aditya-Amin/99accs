<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Cart;
use App\Models\Order;
use App\Services\Payment\PaymentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class WebhookController extends Controller
{
    public function stripe(Request $request)
    {
        try {
            $event = app(PaymentService::class)
                ->provider('stripe')
                ->verifyWebhook($request);
        } catch (\Throwable $e) {
            Log::warning('Stripe webhook rejected: ' . $e->getMessage());
            return response()->json(['message' => 'Invalid signature.'], 400);
        }

        if ($event->status === 'unknown') {
            // Acknowledge events we don't act on so Stripe stops retrying
            return response()->json(['received' => true]);
        }

        $this->handleEvent($event->providerRef, $event->status);

        return response()->json(['received' => true]);
    }

    public function cryptomus(Request $request)
    {
        try {
            $event = app(PaymentService::class)
                ->provider('crypto')
                ->verifyWebhook($request);
        } catch (\Throwable $e) {
            Log::warning('Cryptomus webhook rejected: ' . $e->getMessage());
            return response()->json(['message' => 'Invalid signature.'], 400);
        }

        $this->handleEvent($event->providerRef, $event->status);

        return response()->json(['received' => true]);
    }

    // ─── Shared handler ───────────────────────────────────────────────────────

    private function handleEvent(string $providerRef, string $status): void
    {
        // Cryptomus sends order_id (our checkout_token) as provider ref on some events
        $order = Order::where('payment_provider_id', $providerRef)
            ->orWhere('checkout_token', $providerRef)
            ->first();

        if (! $order) {
            Log::info("Webhook received for unknown order ref: {$providerRef}");
            return;
        }

        // Idempotency: skip if already in final state
        if ($order->isPaid() && $status === 'paid') {
            return;
        }

        if ($status === 'paid') {
            $order->update([
                'payment_status' => 'paid',
                'status'         => Order::STATUS_PROCESSING,
                'paid_at'        => now(),
            ]);

            // Clear the customer's cart now that payment is confirmed
            Cart::where('customer_id', $order->customer_id)
                ->first()
                ?->items()
                ->delete();

            Log::info("Order {$order->number} marked paid via webhook.");
        } elseif ($status === 'failed') {
            // Only revert to pending if not already paid — let admin handle manually
            if (! $order->isPaid()) {
                $order->update(['payment_status' => 'failed']);
            }
        }
    }
}
