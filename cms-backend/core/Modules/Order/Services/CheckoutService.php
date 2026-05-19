<?php
namespace Core\Modules\Order\Services;

use Core\Modules\Cart\Models\Cart;
use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Support\Facades\DB;
use Core\Facades\Hook;
use Core\Facades\Payment;
use Core\Support\PaymentResult;
use RuntimeException;

class CheckoutService {
    public function checkout(Cart $cart, array $data): Order {
        return DB::transaction(function () use ($cart, $data) {
            $totalPrice = $cart->items->sum(fn($item) => $item->quantity * $item->price);
            $paymentMethod = $data['payment_method'] ?? null;
            $order = Order::create(['customer_id' => $cart->user_id, 'status' => Order::STATUS_PENDING, 'total_amount' => $totalPrice, 'payment_method' => $paymentMethod]);
            foreach ($cart->items as $cartItem) {
                OrderItem::create(['order_id' => $order->id, 'product_id' => $cartItem->product_id, 'product_variation_id' => $cartItem->product_variation_id, 'quantity' => $cartItem->quantity, 'price' => $cartItem->price]);
            }

            $result = $this->dispatchPayment($order, $paymentMethod, $data);

            Hook::doAction('order_created', $order);
            if ($result) {
                Hook::doAction('payment_initiated', $order, $result);
            }
            $cart->items()->delete();
            return $order;
        });
    }

    protected function dispatchPayment(Order $order, ?string $paymentMethod, array $data): ?PaymentResult
    {
        if (! $paymentMethod) {
            return null;
        }

        $gateway = Payment::get($paymentMethod);
        if (! $gateway) {
            throw new RuntimeException("Payment gateway '{$paymentMethod}' is not registered.");
        }

        $result = $gateway->charge($order, $data);
        if (! $result->success) {
            throw new RuntimeException("Payment failed: {$result->message}");
        }

        return $result;
    }
}
