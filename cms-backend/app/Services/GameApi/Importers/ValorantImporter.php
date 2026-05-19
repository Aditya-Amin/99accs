<?php

namespace App\Services\GameApi\Importers;

use App\Services\GameApi\Clients\KawitoysSkinChecker;
use App\Services\GameApi\Clients\ValorantApiCom;
use App\Services\GameApi\Contracts\GameApiImporterInterface;
use App\Services\GameApi\DTOs\ProductImportDTO;
use Illuminate\Support\Collection;

class ValorantImporter implements GameApiImporterInterface
{
    public function __construct(
        private readonly KawitoysSkinChecker $checker,
        private readonly ValorantApiCom      $valorantApi,
    ) {}

    public function import(string $username, string $password, ?string $proxy): ProductImportDTO
    {
        // 1. Fetch raw entitlements from kawitoys
        $entitlements = collect($this->checker->check($username, $password, $proxy));

        // 2. Group by TypeID
        $byType = $entitlements->groupBy('TypeID');

        // 3. Fetch catalog from valorant-api.com (cached 24h)
        $allSkinLevels  = $this->valorantApi->getAllSkinLevels();
        $allAgents      = $this->valorantApi->getAllAgents();
        $allBuddyLevels = $this->valorantApi->getAllBuddyLevels();

        // 4. Resolve skins
        $skinTypeId      = config('game_apis.valorant.type_ids.skin_level');
        $skinEntitlements = $byType->get($skinTypeId, collect());
        [$skins, $skinInventory] = $this->resolveSkins($skinEntitlements, $allSkinLevels);

        // 5. Resolve agents
        $agentTypeId      = config('game_apis.valorant.type_ids.agent');
        $agentEntitlements = $byType->get($agentTypeId, collect());
        [$agents, $agentsDetailed] = $this->resolveAgents($agentEntitlements, $allAgents);

        // 6. Resolve buddies (two possible TypeIDs from kawitoys)
        $buddyTypeIds = [
            config('game_apis.valorant.type_ids.buddy_level'),
            config('game_apis.valorant.type_ids.buddy_equippable'),
        ];
        $buddyEntitlements = $byType
            ->filter(fn ($items, $typeId) => in_array($typeId, $buddyTypeIds))
            ->flatten(1);
        [$buddies, $buddyInventory] = $this->resolveBuddies($buddyEntitlements, $allBuddyLevels);

        // 7. Build specs
        $specs = [
            'Skins'   => (string) count($skins),
            'Agents'  => (string) count($agents),
            'Buddies' => (string) count($buddies),
        ];

        return new ProductImportDTO(
            skins:           $skins,
            agents:          $agents,
            buddies:         $buddies,
            specs:           $specs,
            agents_count:    count($agents),
            agents_detailed: $agentsDetailed,
            skin_inventory:  $skinInventory,
            buddy_inventory: $buddyInventory,
            source_provider: 'riot',
        );
    }

    // ─── Private resolvers ────────────────────────────────────────────────────

    private function resolveSkins(Collection $entitlements, array $catalog): array
    {
        $items = $entitlements->map(function ($e) use ($catalog) {
            $uuid = $e['ItemID'];
            $skin = $catalog[$uuid] ?? null;
            return [
                'id'          => $uuid,
                'name'        => $skin['displayName'] ?? 'Unknown Skin',
                'image'       => $skin['displayIcon'] ?? null,
                'rarity'      => null,   // Requires content tier lookup — Phase 4 extension
                'weapon_type' => null,   // Requires parent weapon lookup — Phase 4 extension
                'levels'      => null,
            ];
        })->values()->toArray();

        $names = array_column($items, 'name');

        $inventory = [
            'total'     => count($items),
            'purchased' => count($items),
            'vp'        => 0,
            'items'     => $items,
        ];

        return [$names, $inventory];
    }

    private function resolveAgents(Collection $entitlements, array $catalog): array
    {
        $detailed = $entitlements->map(function ($e) use ($catalog) {
            $uuid  = $e['ItemID'];
            $agent = $catalog[$uuid] ?? null;
            return [
                'id'        => $uuid,
                'image'     => $agent['displayIcon'] ?? null,
                'role_icon' => $agent['role']['displayIcon'] ?? null,
            ];
        })->values()->toArray();

        $names = $entitlements->map(function ($e) use ($catalog) {
            return $catalog[$e['ItemID']]['displayName'] ?? null;
        })->filter()->values()->toArray();

        return [$names, $detailed];
    }

    private function resolveBuddies(Collection $entitlements, array $catalog): array
    {
        $items = $entitlements->unique('ItemID')->map(function ($e) use ($catalog) {
            $uuid  = $e['ItemID'];
            $buddy = $catalog[$uuid] ?? null;
            return [
                'id'    => $uuid,
                'name'  => $buddy['displayName'] ?? 'Unknown Buddy',
                'image' => $buddy['displayIcon'] ?? null,
            ];
        })->values()->toArray();

        $names = array_column($items, 'name');

        $inventory = [
            'total'     => count($items),
            'purchased' => 0,
            'vp'        => 0,
            'items'     => $items,
        ];

        return [$names, $inventory];
    }
}
