<?php

namespace App\Services\Payment;

use App\Models\Order;
use App\Services\Payment\Contracts\PaymentProviderInterface;
use App\Services\Payment\DTOs\PaymentResultDTO;
use App\Services\Payment\Providers\CryptomusProvider;
use App\Services\Payment\Providers\StripeProvider;
use InvalidArgumentException;

class PaymentService
{
    private array $providers;

    public function __construct(
        StripeProvider   $stripe,
        CryptomusProvider $cryptomus,
    ) {
        $this->providers = [
            'stripe' => $stripe,
            'crypto' => $cryptomus,
        ];
    }

    public function createPayment(Order $order, string $method): PaymentResultDTO
    {
        $provider = $this->providers[$method] ?? null;

        if (! $provider instanceof PaymentProviderInterface) {
            throw new InvalidArgumentException(
                "Unsupported payment method: {$method}. Supported: stripe, crypto."
            );
        }

        return $provider->createPayment($order);
    }

    public function provider(string $name): PaymentProviderInterface
    {
        return $this->providers[$name]
            ?? throw new InvalidArgumentException("Unknown payment provider: {$name}");
    }
}
