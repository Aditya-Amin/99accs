<?php

namespace App\Services\GameApi\Importers;

use App\Services\GameApi\Contracts\GameApiImporterInterface;
use App\Services\GameApi\DTOs\ProductImportDTO;
use RuntimeException;

class LegendsImporter implements GameApiImporterInterface
{
    // Phase 4 extension — Riot LoL API integration pending.

    public function import(string $username, string $password, ?string $proxy): ProductImportDTO
    {
        throw new RuntimeException(
            'League of Legends API import is not yet configured. ' .
            'Please fill in League product details manually for now.'
        );
    }
}
