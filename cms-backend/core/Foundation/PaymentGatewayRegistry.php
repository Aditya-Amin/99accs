<?php

namespace Core\Foundation;

use App\Models\PaymentGateway;
use Core\Contracts\PaymentGatewayContract;

class PaymentGatewayRegistry
{
    /** @var array<string, PaymentGatewayContract> */
    protected array $gateways = [];

    public function register(PaymentGatewayContract $gateway): void
    {
        $this->gateways[$gateway->getSlug()] = $gateway;
    }

    public function get(string $slug): ?PaymentGatewayContract
    {
        return $this->gateways[$slug] ?? null;
    }

    /** @return array<string, PaymentGatewayContract> */
    public function all(): array
    {
        return $this->gateways;
    }

    /** @return array<string, PaymentGatewayContract> */
    public function available(): array
    {
        $activeSlugs = PaymentGateway::where('is_active', true)->pluck('slug')->all();

        return array_intersect_key($this->gateways, array_flip($activeSlugs));
    }

    public function has(string $slug): bool
    {
        return isset($this->gateways[$slug]);
    }
}
