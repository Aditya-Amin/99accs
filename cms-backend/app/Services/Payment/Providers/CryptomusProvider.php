<?php

namespace App\Services\Payment\Providers;

use App\Models\Order;
use App\Services\Payment\Contracts\PaymentProviderInterface;
use App\Services\Payment\DTOs\PaymentResultDTO;
use App\Services\Payment\DTOs\WebhookEventDTO;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class CryptomusProvider implements PaymentProviderInterface
{
    private string $merchantId;
    private string $paymentKey;
    private string $callbackUrl;

    private const API_BASE = 'https://api.cryptomus.com/v1';

    // Statuses Cryptomus sends that mean "payment received"
    private const PAID_STATUSES = ['paid', 'paid_over', 'wrong_amount_waiting'];

    public function __construct()
    {
        $this->merchantId  = config('services.cryptomus.merchant_id', '');
        $this->paymentKey  = config('services.cryptomus.payment_key', '');
        $this->callbackUrl = config('services.cryptomus.callback_url', '');
    }

    public function createPayment(Order $order): PaymentResultDTO
    {
        if (! $this->merchantId || ! $this->paymentKey) {
            throw new RuntimeException(
                'CRYPTOMUS_MERCHANT_ID and CRYPTOMUS_PAYMENT_KEY are required. ' .
                'Add them to your .env file.'
            );
        }

        $body = [
            'amount'               => number_format((float) $order->total_price, 2, '.', ''),
            'currency'             => 'USD',
            'order_id'             => $order->checkout_token,
            'url_callback'         => $this->callbackUrl,
            'url_return'           => rtrim(config('app.url'), '/') . '/checkout/success',
            'url_success'          => rtrim(config('app.url'), '/') . '/checkout/success',
            'is_payment_multiple'  => false,
            'lifetime'             => 3600,
        ];

        $response = Http::timeout(30)
            ->withHeaders([
                'merchant'     => $this->merchantId,
                'sign'         => $this->buildSign($body),
                'Content-Type' => 'application/json',
            ])
            ->post(self::API_BASE . '/payment', $body);

        if (! $response->successful()) {
            throw new RuntimeException(
                'Cryptomus payment creation failed: ' . $response->body()
            );
        }

        $result = $response->json('result');

        if (! $result || ! isset($result['uuid'], $result['url'])) {
            throw new RuntimeException(
                'Unexpected Cryptomus response: ' . $response->body()
            );
        }

        return new PaymentResultDTO(
            paymentMethod: 'crypto',
            providerRef:   $result['uuid'],
            clientSecret:  null,
            paymentUrl:    $result['url'],
        );
    }

    public function verifyWebhook(Request $request): WebhookEventDTO
    {
        $data = $request->all();
        $receivedSign = $data['sign'] ?? '';

        // Reconstruct body without sign field for verification
        $body = $data;
        unset($body['sign']);

        $expectedSign = md5(base64_encode(json_encode($body)) . $this->paymentKey);

        if (! hash_equals($expectedSign, $receivedSign)) {
            throw new RuntimeException('Invalid Cryptomus webhook signature.');
        }

        $status = in_array($data['status'] ?? '', self::PAID_STATUSES, true)
            ? 'paid'
            : 'failed';

        return new WebhookEventDTO(
            providerRef: $data['uuid'] ?? $data['order_id'] ?? '',
            status:      $status,
            provider:    'cryptomus',
        );
    }

    /**
     * Sign = MD5( base64(json_body) + payment_api_key )
     */
    private function buildSign(array $body): string
    {
        return md5(base64_encode(json_encode($body)) . $this->paymentKey);
    }
}
