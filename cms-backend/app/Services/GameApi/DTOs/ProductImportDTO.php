<?php

namespace App\Services\GameApi\DTOs;

class ProductImportDTO
{
    public function __construct(
        public readonly array   $skins,
        public readonly array   $agents,
        public readonly array   $buddies,
        public readonly array   $specs,
        public readonly int     $agents_count,
        public readonly array   $agents_detailed,
        public readonly ?array  $skin_inventory,
        public readonly ?array  $buddy_inventory,
        public readonly string  $source_provider = 'riot',
    ) {}

    public function toFormArray(): array
    {
        return [
            'skins'           => $this->skins,
            'agents'          => $this->agents,
            'buddies'         => $this->buddies,
            'specs'           => $this->specs,
            'agents_count'    => $this->agents_count,
            'agents_detailed' => $this->agents_detailed,
            'skin_inventory'  => $this->skin_inventory,
            'buddy_inventory' => $this->buddy_inventory,
            'source_provider' => $this->source_provider,
            'synced_at'       => now()->toDateTimeString(),
        ];
    }
}
