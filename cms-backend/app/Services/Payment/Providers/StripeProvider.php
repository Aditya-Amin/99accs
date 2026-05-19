<?php

namespace App\Services\Payment\Providers;

use App\Models\Order;
use App\Services\Payment\Contracts\PaymentProviderInterface;
use App\Services\Payment\DTOs\PaymentResultDTO;
use App\Services\Payment\DTOs\WebhookEventDTO;
use Illuminate\Http\Request;
use RuntimeException;
use Stripe\Exception\SignatureVerificationException;
use Stripe\PaymentIntent;
use Stripe\Stripe;
use Stripe\Webhook;

class StripeProvider implements PaymentProviderInterface
{
    public function __construct()
    {
        Stripe::setApiKey(config('services.stripe.secret'));
    }

    public function createPayment(Order $order): PaymentResultDTO
    {
        // Stripe requires amounts in the smallest currency unit (cents for USD)
        $amountCents = (int) round((float) $order->total_price * 100);

        $intent = PaymentIntent::create([
            'amount'   => $amountCents,
            'currency' => 'usd',
            'metadata' => [
                'order_id'       => $order->id,
                'checkout_token' => $order->checkout_token,
                'order_number'   => $order->number,
            ],
            'automatic_payment_methods' => ['enabled' => true],
        ]);

        return new PaymentResultDTO(
            paymentMethod: 'stripe',
            providerRef:   $intent->id,
            clientSecret:  $intent->client_secret,
            paymentUrl:    null,
        );
    }

    public function verifyWebhook(Request $request): WebhookEventDTO
    {
        $secret = config('services.stripe.webhook_secret');

        if (! $secret) {
            throw new RuntimeException('STRIPE_WEBHOOK_SECRET is not configured.');
        }

        try {
            $event = Webhook::constructEvent(
                $request->getContent(),
                $request->header('Stripe-Signature', ''),
                $secret,
            );
        } catch (SignatureVerificationException $e) {
            throw new RuntimeException('Stripe webhook signature verification failed: ' . $e->getMessage());
        }

        /** @var \Stripe\PaymentIntent $intent */
        $intent = $event->data->object;

        $status = match ($event->type) {
            'payment_intent.succeeded'        => 'paid',
            'payment_intent.payment_failed'   => 'failed',
            'payment_intent.canceled'         => 'failed',
            default                           => 'unknown',
        };

        return new WebhookEventDTO(
            providerRef: $intent->id,
            status:      $status,
            provider:    'stripe',
        );
    }
}
