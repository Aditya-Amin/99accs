<?php

namespace App\Services\Payment\Contracts;

use App\Models\Order;
use App\Services\Payment\DTOs\PaymentResultDTO;
use App\Services\Payment\DTOs\WebhookEventDTO;
use Illuminate\Http\Request;

interface PaymentProviderInterface
{
    public function createPayment(Order $order): PaymentResultDTO;

    public function verifyWebhook(Request $request): WebhookEventDTO;
}
