<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Order;
use App\Models\PaymentGateway;
use App\Models\Product;
use App\Notifications\GuestCheckoutSetupNotification;
use App\Services\Payment\PaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

/**
 * Checkout flow supports both signed-in customers and guests.
 *
 *   - Authed user: $request->user() resolves the customer; email/phone are
 *     optional. Order is associated with the user immediately.
 *
 *   - Guest: client must supply email + phone + first_name. We find-or-create
 *     a Customer with `must_reset_password=true` and dispatch
 *     GuestCheckoutSetupNotification so the buyer can set a real password
 *     from the email link. If the email already belongs to a customer who has
 *     already set a password we reject (would let a guest enrich someone
 *     else's order history with a typo'd email — bad).
 *
 *   - Order access (show / pay / cancel) is gated by the checkout_token UUID.
 *     Anyone with that URL can manage the order, which matches industry
 *     convention for guest order tracking (the UUID is unguessable).
 */
class CheckoutController extends Controller
{
    public function create(Request $request): JsonResponse
    {
        $data = $request->validate([
            'items'                 => 'required|array|min:1',
            'items.*.product_id'    => 'required|integer|exists:products,id',
            'items.*.quantity'      => 'required|integer|min:1|max:99',
            'note'                  => 'nullable|string|max:500',
            // Guest-only fields (validated again below when no user is present)
            'email'                 => 'nullable|email|max:255',
            'phone'                 => 'nullable|string|max:30',
            'first_name'            => 'nullable|string|max:255',
            'last_name'             => 'nullable|string|max:255',
        ]);

        // The /checkout routes deliberately omit auth:sanctum middleware so
        // guests can hit them. Resolve the sanctum guard explicitly: returns
        // the Customer if a valid bearer token was sent, null otherwise.
        $customer = auth('sanctum')->user();
        $isGuestCheckout = $customer === null;

        if ($isGuestCheckout) {
            $request->validate([
                'email'      => 'required|email|max:255',
                'phone'      => 'required|string|max:30',
                'first_name' => 'required|string|max:255',
            ]);

            $customerResult = $this->resolveGuestCustomer($data);
            if ($customerResult instanceof JsonResponse) {
                return $customerResult;
            }
            [$customer, $isFreshGuestAccount] = $customerResult;
        } else {
            $isFreshGuestAccount = false;
        }

        // Lock products + validate stock + compute totals from live DB prices.
        // Trusting client-side prices would let a tampered cart underpay the
        // order, so we always re-resolve from the products table.
        $products = Product::whereIn('id', collect($data['items'])->pluck('product_id'))
            ->lockForUpdate()
            ->get()
            ->keyBy('id');

        $lineItems = [];
        $subtotal  = 0.0;
        foreach ($data['items'] as $line) {
            $product = $products->get($line['product_id']);
            if (! $product) {
                return response()->json([
                    'code'    => 'PRODUCT_MISSING',
                    'message' => 'One or more products in your cart no longer exist.',
                ], 422);
            }
            if ($product->stock_qty < $line['quantity']) {
                return response()->json([
                    'code'    => 'OUT_OF_STOCK',
                    'message' => "'{$product->name}' does not have enough stock available.",
                ], 422);
            }
            $unitPrice = (float) $product->price;
            $subtotal += $unitPrice * $line['quantity'];
            $lineItems[] = [
                'product'  => $product,
                'quantity' => $line['quantity'],
                'price'    => $unitPrice,
            ];
        }

        $order = DB::transaction(function () use ($customer, $lineItems, $subtotal) {
            $order = Order::create([
                'customer_id'    => $customer->id,
                'number'         => $this->generateOrderNumber(),
                'checkout_token' => (string) Str::uuid(),
                'status'         => Order::STATUS_PENDING,
                'payment_status' => 'pending',
                'total_price'    => $subtotal,
                'expires_at'     => now()->addHour(),
            ]);

            foreach ($lineItems as $line) {
                $order->items()->create([
                    'product_id'             => $line['product']->id,
                    'quantity'               => $line['quantity'],
                    'price_snapshot'         => $line['price'],
                    'product_name_snapshot'  => $line['product']->name,
                    'product_image_snapshot' => $line['product']->featured_image_url,
                ]);
                $line['product']->decrement('stock_qty', $line['quantity']);
            }

            return $order;
        });

        // Fire the welcome/password-setup mail AFTER the order persists so we
        // never email a user about an order that rolled back.
        if ($isFreshGuestAccount) {
            $this->sendGuestSetupEmail($customer, $order);
        }

        return response()->json(
            [
                'data' => array_merge(
                    $this->formatCheckout($order->load('items')),
                    [
                        // Tells the frontend whether to show the "check your
                        // email to set a password" notice on the success page.
                        'is_guest_checkout' => $isGuestCheckout,
                        'customer_email'    => $customer->email,
                    ],
                ),
            ],
            201,
        );
    }

    public function show(string $token): JsonResponse
    {
        $order = $this->findOrder($token)->load('items', 'customer');
        $data  = $this->formatCheckout($order);
        $data['customer_email']    = $order->customer?->email;
        $data['is_guest_checkout'] = (bool) $order->customer?->must_reset_password;
        return response()->json(['data' => $data]);
    }

    public function pay(Request $request, string $token): JsonResponse
    {
        // Validate against gateways that are currently enabled in the admin
        // dashboard. A gateway can be enabled/disabled at any time, and only
        // enabled ones are accepted as payment methods.
        $allowedMethods = PaymentGateway::active()->pluck('slug')->all();

        if (empty($allowedMethods)) {
            return response()->json([
                'code'    => 'NO_PAYMENT_GATEWAYS',
                'message' => 'No payment gateways are currently available. Please contact support.',
            ], 503);
        }

        $request->validate([
            'payment_method' => ['required', Rule::in($allowedMethods)],
        ]);

        $order = $this->findOrder($token);

        if ($order->isPaid()) {
            return response()->json(['code' => 'ALREADY_PAID', 'message' => 'This order is already paid.'], 409);
        }
        if ($order->status === Order::STATUS_CANCELLED) {
            return response()->json(['code' => 'ORDER_CANCELLED', 'message' => 'This order has been cancelled.'], 410);
        }
        if ($order->isExpired()) {
            return response()->json(['code' => 'ORDER_EXPIRED', 'message' => 'This checkout session has expired. Please start a new order.'], 410);
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
                'client_secret'  => $result->clientSecret,
                'payment_url'    => $result->paymentUrl,
            ],
        ]);
    }

    public function cancel(string $token): JsonResponse
    {
        $order = $this->findOrder($token);

        if ($order->isPaid()) {
            return response()->json(['code' => 'ALREADY_PAID', 'message' => 'Paid orders cannot be cancelled here. Contact support.'], 422);
        }
        if ($order->status === Order::STATUS_CANCELLED) {
            return response()->json(['code' => 'ALREADY_CANCELLED', 'message' => 'Order is already cancelled.'], 409);
        }

        DB::transaction(function () use ($order) {
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

    /**
     * @return array{0: Customer, 1: bool}|JsonResponse  Tuple of [customer, isFreshAccount] on
     *                                                  success; JsonResponse on rejection.
     */
    private function resolveGuestCustomer(array $data): array|JsonResponse
    {
        $email = strtolower(trim($data['email']));
        $existing = Customer::where('email', $email)->first();

        if ($existing) {
            if ($existing->is_blocked) {
                return response()->json([
                    'code'    => 'ACCOUNT_BLOCKED',
                    'message' => 'This account has been blocked. Contact support.',
                ], 403);
            }
            // If the account is fully set up (password set), refuse to attach
            // the order to it without authentication — otherwise a guest could
            // pile orders onto someone else's history with a typo'd email.
            if (! $existing->must_reset_password) {
                return response()->json([
                    'code'    => 'EMAIL_REQUIRES_LOGIN',
                    'message' => 'An account already exists for this email. Please sign in to complete your purchase.',
                    'email'   => $email,
                ], 409);
            }
            // Existing pending-setup guest — refresh their phone/name and
            // re-issue the setup email (their previous link likely expired).
            $existing->fill([
                'first_name' => $data['first_name'] ?: $existing->first_name,
                'last_name'  => $data['last_name']  ?? $existing->last_name,
                'phone'      => $data['phone']      ?: $existing->phone,
            ])->save();
            return [$existing, true];
        }

        // Fresh guest — random unguessable password; setup link arrives by email.
        $customer = Customer::create([
            'first_name'          => $data['first_name'],
            'last_name'           => $data['last_name'] ?? '',
            'email'               => $email,
            'phone'               => $data['phone'],
            'password'            => Hash::make(Str::random(64)),
            'must_reset_password' => true,
        ]);

        return [$customer, true];
    }

    private function sendGuestSetupEmail(Customer $customer, Order $order): void
    {
        // Use the customers broker so the reset token is keyed to this guard,
        // matching ResetPasswordController::reset().
        $token = Password::broker('customers')->createToken($customer);
        $customer->notify(new GuestCheckoutSetupNotification($token, $order));
    }

    private function findOrder(string $token): Order
    {
        // checkout_token is a UUID; presence of the token IS the auth signal.
        return Order::where('checkout_token', $token)->firstOrFail();
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
            'payment_methods_available' => PaymentGateway::active()->ordered()->pluck('slug')->all(),
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
