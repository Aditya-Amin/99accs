<?php

namespace Core\Support;

class PaymentResult
{
    public function __construct(
        public readonly bool $success,
        public readonly ?string $transactionId = null,
        public readonly ?string $message = null,
        public readonly ?string $redirectUrl = null,
        public readonly array $raw = [],
    ) {}

    public static function success(?string $transactionId = null, ?string $redirectUrl = null, array $raw = []): self
    {
        return new self(true, $transactionId, null, $redirectUrl, $raw);
    }

    public static function failure(string $message, array $raw = []): self
    {
        return new self(false, null, $message, null, $raw);
    }
}
