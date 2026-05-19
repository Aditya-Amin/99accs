<?php

namespace Core\Plugins\PayPal\Gateways;

use App\Models\Order;
use App\Models\PaymentGateway;
use Core\Contracts\PaymentGatewayContract;
use Core\Support\PaymentResult;

class PayPalGateway implements PaymentGatewayContract
{
    public function getSlug(): string
    {
        return 'paypal';
    }

    public function getLabel(): string
    {
        return 'PayPal';
    }

    public function charge(Order $order, array $payload = []): PaymentResult
    {
        $config = $this->config();
        if (! $config) {
            return PaymentResult::failure('PayPal gateway is not configured.');
        }

        // TODO: call PayPal Orders API with $config->public_key / $config->secret_key
        // and return the approval redirect URL.
        return PaymentResult::success(
            transactionId: 'paypal_pending_'.$order->id,
            raw: ['note' => 'stub — replace with PayPal SDK call'],
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
