<?php

namespace App\Services\Payment;

use App\Models\Order;
use App\Models\PaymentGateway;
use App\Services\Payment\Contracts\PaymentProviderInterface;
use App\Services\Payment\DTOs\PaymentResultDTO;
use App\Services\Payment\Providers\CryptomusProvider;
use App\Services\Payment\Providers\StripeProvider;
use InvalidArgumentException;
use RuntimeException;

/**
 * Resolves the right payment provider for a given gateway slug.
 *
 * Provider implementations are stateless except for the PaymentGateway row
 * they're hydrated with — credentials live in the DB (encrypted JSON column)
 * and are managed by admins via the Filament dashboard. No payment secret
 * should ever live in .env.
 *
 * Provider class binding is hardcoded here on purpose: each provider has a
 * unique credential shape and webhook signature scheme, so they need
 * bespoke code. Adding a new gateway means adding a row + a provider class
 * + a case in `resolveProvider()`.
 */
class PaymentService
{
    public function createPayment(Order $order, string $method): PaymentResultDTO
    {
        return $this->provider($method)->createPayment($order);
    }

    public function provider(string $method): PaymentProviderInterface
    {
        $gateway = PaymentGateway::where('slug', $method)->first();

        if (! $gateway) {
            throw new InvalidArgumentException(
                "Unknown payment gateway: '{$method}'. Configure it under Settings → Payment Gateways."
            );
        }

        if (! $gateway->is_active) {
            throw new RuntimeException(
                "Payment gateway '{$gateway->name}' is disabled. Enable it under Settings → Payment Gateways."
            );
        }

        return $this->resolveProvider($gateway);
    }

    public function availableSlugs(): array
    {
        return PaymentGateway::active()->ordered()->pluck('slug')->all();
    }

    private function resolveProvider(PaymentGateway $gateway): PaymentProviderInterface
    {
        return match ($gateway->slug) {
            'stripe' => new StripeProvider($gateway),
            'crypto' => new CryptomusProvider($gateway),
            default  => throw new InvalidArgumentException(
                "No driver implementation for gateway slug '{$gateway->slug}'."
            ),
        };
    }
}
