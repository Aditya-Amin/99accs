<?php

namespace Core\Contracts;

use App\Models\Order;
use Core\Support\PaymentResult;

interface PaymentGatewayContract
{
    public function getSlug(): string;

    public function getLabel(): string;

    public function charge(Order $order, array $payload = []): PaymentResult;

    public function refund(Order $order, ?float $amount = null): PaymentResult;

    public function handleWebhook(array $payload): PaymentResult;
}
