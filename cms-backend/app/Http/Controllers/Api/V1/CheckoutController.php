<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Cart;
use App\Models\Order;
use App\Services\Payment\PaymentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CheckoutController extends Controller
{
    public function create(Request $request)
    {
        $customer = $request->user();

        $cart = Cart::where('customer_id', $customer->id)
            ->with('items.product')
            ->first();

        if (! $cart || $cart->items->isEmpty()) {
            return response()->json(['message' => 'Your cart is empty.'], 422);
        }

        // Validate stock for all items upfront
        foreach ($cart->items as $item) {
            if (! $item->product) {
                return response()->json([
                    'message' => 'One or more products in your cart no longer exist.',
                ], 422);
            }
            if ($item->product->stock_qty < $item->quantity) {
                return response()->json([
                    'message' => "'{$item->product->name}' does not have enough stock.",
                ], 422);
            }
        }

        $subtotal = $cart->items->sum(fn ($i) => (float) $i->price * $i->quantity);

        $order = DB::transaction(function () use ($customer, $cart, $subtotal) {
            $order = Order::create([
                'customer_id'    => $customer->id,
                'number'         => $this->generateOrderNumber(),
                'checkout_token' => (string) Str::uuid(),
                'status'         => Order::STATUS_PENDING,
                'payment_status' => 'pending',
                'total_price'    => $subtotal,
                'expires_at'     => now()->addHour(),
            ]);

            foreach ($cart->items as $item) {
                $order->items()->create([
                    'product_id'             => $item->product_id,
                    'quantity'               => $item->quantity,
                    'price_snapshot'         => $item->price,
                    'product_name_snapshot'  => $item->product->name,
                    'product_image_snapshot' => $item->product->featured_image_url,
                ]);

                // Reserve stock
                $item->product->decrement('stock_qty', $item->quantity);
            }

            return $order;
        });

        return response()->json(
            ['data' => $this->formatCheckout($order->load('items'))],
            201
        );
    }

    public function show(Request $request, string $token)
    {
        $order = $this->findOrder($token, $request->user()->id);

        return response()->json(['data' => $this->formatCheckout($order->load('items'))]);
    }

    public function pay(Request $request, string $token)
    {
        $request->validate([
            'payment_method' => 'required|in:stripe,crypto',
        ]);

        $order = $this->findOrder($token, $request->user()->id);

        if ($order->isPaid()) {
            return response()->json(['message' => 'This order is already paid.'], 409);
        }

        if ($order->status === Order::STATUS_CANCELLED) {
            return response()->json(['message' => 'This order has been cancelled.'], 410);
        }

        if ($order->isExpired()) {
            return response()->json(['message' => 'This checkout session has expired. Please start a new order.'], 410);
        }

        $result = app(PaymentService::class)->createPayment($order, $request->string('payment_method'));

        $order->update([
            'payment_method'      => $result->paymentMethod,
            'payment_provider_id' => $result->providerRef,
            'client_secret'       => $result->clientSecret,
            'payment_url'         => $result->paymentUrl,
        ]);

        return response()->json([
            'data' => [
                'payment_method' => $result->paymentMethod,
                'client_secret'  => $result->clientSecret,    // Stripe: pass to PaymentElement
                'payment_url'    => $result->paymentUrl,      // Cryptomus: redirect here
            ],
        ]);
    }

    public function cancel(Request $request, string $token)
    {
        $order = $this->findOrder($token, $request->user()->id);

        if ($order->isPaid()) {
            return response()->json(['message' => 'Paid orders cannot be cancelled here. Contact support.'], 422);
        }

        if ($order->status === Order::STATUS_CANCELLED) {
            return response()->json(['message' => 'Order is already cancelled.'], 409);
        }

        DB::transaction(function () use ($order) {
            // Restore reserved stock
            $order->load('items.product');
            foreach ($order->items as $item) {
                $item->product?->increment('stock_qty', $item->quantity);
            }

            $order->update([
                'status'         => Order::STATUS_CANCELLED,
                'payment_status' => 'cancelled',
            ]);
        });

        return response()->json(['message' => 'Order cancelled successfully.']);
    }

    // ─── Helpers ─────────────────────────────────────────────────────────────

    private function findOrder(string $token, int $customerId): Order
    {
        return Order::where('checkout_token', $token)
            ->where('customer_id', $customerId)
            ->firstOrFail();
    }

    private function generateOrderNumber(): string
    {
        return 'ORD-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -6));
    }

    private function formatCheckout(Order $order): array
    {
        return [
            'id'             => $order->checkout_token,
            'order_number'   => $order->number,
            'status'         => $order->status,
            'payment_status' => $order->payment_status,
            'payment_method' => $order->payment_method,
            'subtotal'       => (float) $order->total_price,
            'total'          => (float) $order->total_price,
            'expires_at'     => $order->expires_at?->toISOString(),
            'payment_methods_available' => ['stripe', 'crypto'],
            'items'          => $order->items->map(fn ($item) => [
                'id'       => $item->id,
                'title'    => $item->product_name_snapshot,
                'image'    => $item->product_image_snapshot,
                'price'    => (float) $item->price_snapshot,
                'quantity' => $item->quantity,
                'subtotal' => (float) ($item->price_snapshot * $item->quantity),
            ])->toArray(),
        ];
    }
}
