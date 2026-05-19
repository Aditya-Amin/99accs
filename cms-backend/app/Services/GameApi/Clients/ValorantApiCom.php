<?php

namespace App\Services\GameApi\Clients;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class ValorantApiCom
{
    private string $base;
    private int    $ttl;

    public function __construct()
    {
        $this->base = rtrim(config('game_apis.valorant.api_base', 'https://valorant-api.com/v1'), '/');
        $this->ttl  = config('game_apis.valorant.cache_ttl', 86400);
    }

    // Returns skin levels keyed by UUID
    public function getAllSkinLevels(): array
    {
        return Cache::remember('valorant_api.skin_levels', $this->ttl, function () {
            $resp = Http::timeout(30)->get("{$this->base}/weapons/skinlevels");
            $this->assertOk($resp, 'skin levels');
            return collect($resp->json('data', []))
                ->keyBy('uuid')
                ->toArray();
        });
    }

    // Returns agents keyed by UUID (playable only)
    public function getAllAgents(): array
    {
        return Cache::remember('valorant_api.agents', $this->ttl, function () {
            $resp = Http::timeout(30)->get("{$this->base}/agents", ['isPlayableCharacter' => 'true']);
            $this->assertOk($resp, 'agents');
            return collect($resp->json('data', []))
                ->keyBy('uuid')
                ->toArray();
        });
    }

    // Returns buddy levels keyed by UUID
    public function getAllBuddyLevels(): array
    {
        return Cache::remember('valorant_api.buddy_levels', $this->ttl, function () {
            $resp = Http::timeout(30)->get("{$this->base}/buddies/levels");
            $this->assertOk($resp, 'buddy levels');
            return collect($resp->json('data', []))
                ->keyBy('uuid')
                ->toArray();
        });
    }

    public function clearCache(): void
    {
        Cache::forget('valorant_api.skin_levels');
        Cache::forget('valorant_api.agents');
        Cache::forget('valorant_api.buddy_levels');
    }

    private function assertOk($resp, string $resource): void
    {
        if (! $resp->successful()) {
            throw new RuntimeException("valorant-api.com failed to fetch {$resource}: HTTP {$resp->status()}");
        }
    }
}
