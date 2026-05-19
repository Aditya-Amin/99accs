<?php

namespace App\Services\Payment\DTOs;

class PaymentResultDTO
{
    public function __construct(
        public readonly string  $paymentMethod,  // stripe | crypto
        public readonly string  $providerRef,    // PaymentIntent ID or Cryptomus UUID
        public readonly ?string $clientSecret,   // Stripe only — for frontend PaymentElement
        public readonly ?string $paymentUrl,     // Cryptomus only — redirect to payment page
    ) {}
}
