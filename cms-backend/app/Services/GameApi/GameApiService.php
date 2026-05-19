<?php

namespace App\Services\GameApi;

use App\Services\GameApi\Contracts\GameApiImporterInterface;
use App\Services\GameApi\DTOs\ProductImportDTO;
use App\Services\GameApi\Importers\FortniteImporter;
use App\Services\GameApi\Importers\LegendsImporter;
use App\Services\GameApi\Importers\ValorantImporter;
use InvalidArgumentException;

class GameApiService
{
    private array $importers;

    public function __construct(
        ValorantImporter $valorant,
        FortniteImporter $fortnite,
        LegendsImporter  $legends,
    ) {
        $this->importers = [
            'valorant' => $valorant,
            'fortnite' => $fortnite,
            'legends'  => $legends,
        ];
    }

    public function import(
        string  $game,
        string  $username,
        string  $password,
        ?string $proxy = null
    ): ProductImportDTO {
        $importer = $this->importers[$game] ?? null;

        if (! $importer instanceof GameApiImporterInterface) {
            throw new InvalidArgumentException("No importer configured for game: {$game}");
        }

        return $importer->import($username, $password, $proxy);
    }
}
