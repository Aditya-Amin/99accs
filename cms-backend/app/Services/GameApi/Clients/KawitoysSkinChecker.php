<?php

namespace App\Services\GameApi\Clients;

use Illuminate\Support\Facades\Http;
use RuntimeException;

class KawitoysSkinChecker
{
    private string $endpoint;
    private string $userField;
    private string $passField;
    private string $proxyField;
    private int    $timeout;

    public function __construct()
    {
        $cfg              = config('game_apis.valorant');
        $this->endpoint   = $cfg['checker_endpoint'];
        $this->userField  = $cfg['checker_user_field'];
        $this->passField  = $cfg['checker_pass_field'];
        $this->proxyField = $cfg['checker_proxy_field'];
        $this->timeout    = $cfg['checker_timeout'];
    }

    /**
     * Send credentials to the skin checker and return raw entitlements.
     *
     * @return array<int, array{TypeID: string, ItemID: string, Tiers: mixed}>
     * @throws RuntimeException on failure
     */
    public function check(string $username, string $password, ?string $proxy): array
    {
        $options = [
            $this->userField => $username,
            $this->passField => $password,
        ];

        if ($proxy) {
            $options[$this->proxyField] = $proxy;
        }

        $request = Http::timeout($this->timeout)->asForm();

        if ($proxy) {
            $request = $request->withOptions(['proxy' => $this->buildProxyUrl($proxy)]);
        }

        $response = $request->post($this->endpoint, $options);

        if (! $response->successful()) {
            throw new RuntimeException(
                "Skin checker request failed (HTTP {$response->status()}). " .
                "Verify KAWITOYS_ENDPOINT in .env and that credentials are correct."
            );
        }

        $body = $response->json();

        if ($body === null) {
            throw new RuntimeException(
                "Skin checker returned non-JSON response. " .
                "Check KAWITOYS_ENDPOINT — it may need to point to the API path, not the HTML page."
            );
        }

        // Handle both bare array and wrapped {data: [...]} responses
        if (isset($body['data']) && is_array($body['data'])) {
            return $body['data'];
        }

        if (is_array($body) && isset($body[0]['ItemID'])) {
            return $body;
        }

        if (isset($body['error'])) {
            throw new RuntimeException("Skin checker error: " . $body['error']);
        }

        throw new RuntimeException(
            "Unexpected response format from skin checker. " .
            "Raw response: " . mb_strimwidth($response->body(), 0, 200)
        );
    }

    /**
     * Parse proxy string "host:port:user:pass" → "http://user:pass@host:port"
     */
    private function buildProxyUrl(string $proxy): string
    {
        // Already formatted
        if (str_starts_with($proxy, 'http://') || str_starts_with($proxy, 'https://')) {
            return $proxy;
        }

        $parts = explode(':', $proxy, 4);

        if (count($parts) === 4) {
            [$host, $port, $user, $pass] = $parts;
            return "http://{$user}:{$pass}@{$host}:{$port}";
        }

        if (count($parts) === 2) {
            // host:port — no auth
            [$host, $port] = $parts;
            return "http://{$host}:{$port}";
        }

        return "http://{$proxy}";
    }
}
