<?php

namespace App\Services\Payment\DTOs;

class WebhookEventDTO
{
    public function __construct(
        public readonly string $providerRef,  // PaymentIntent ID or Cryptomus UUID
        public readonly string $status,       // paid | failed | refunded | unknown
        public readonly string $provider,     // stripe | cryptomus
    ) {}
}
