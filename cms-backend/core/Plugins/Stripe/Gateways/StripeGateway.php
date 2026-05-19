<?php

namespace Core\Plugins\Stripe\Gateways;

use App\Models\Order;
use App\Models\PaymentGateway;
use Core\Contracts\PaymentGatewayContract;
use Core\Support\PaymentResult;

class StripeGateway implements PaymentGatewayContract
{
    public function getSlug(): string
    {
        return 'stripe';
    }

    public function getLabel(): string
    {
        return 'Stripe Secure Checkout';
    }

    public function charge(Order $order, array $payload = []): PaymentResult
    {
        $config = $this->config();
        if (! $config) {
            return PaymentResult::failure('Stripe gateway is not configured.');
        }

        // TODO: integrate the Stripe PHP SDK using $config->secret_key
        // and create a PaymentIntent / Checkout Session for $order.
        return PaymentResult::success(
            transactionId: 'stripe_pending_'.$order->id,
            raw: ['note' => 'stub — replace with Stripe SDK call'],
        );
    }

    public function refund(Order $order, ?float $amount = null): PaymentResult
    {
        return PaymentResult::failure('Refund flow not implemented yet.');
    }

    public function handleWebhook(array $payload): PaymentResult
    {
        return PaymentResult::success(transactionId: $payload['id'] ?? null, raw: $payload);
    }

    protected function config(): ?PaymentGateway
    {
        return PaymentGateway::where('slug', $this->getSlug())
            ->where('is_active', true)
            ->first();
    }
}
