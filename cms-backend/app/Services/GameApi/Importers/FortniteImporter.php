<?php

namespace App\Services\GameApi\Importers;

use App\Services\GameApi\Contracts\GameApiImporterInterface;
use App\Services\GameApi\DTOs\ProductImportDTO;
use RuntimeException;

class FortniteImporter implements GameApiImporterInterface
{
    // Phase 4 extension — Epic API / fortnite-api.com integration pending.

    public function import(string $username, string $password, ?string $proxy): ProductImportDTO
    {
        throw new RuntimeException(
            'Fortnite API import is not yet configured. ' .
            'Please fill in Fortnite product details manually for now.'
        );
    }
}
