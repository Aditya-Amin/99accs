<?php

namespace App\Services\GameApi\Contracts;

use App\Services\GameApi\DTOs\ProductImportDTO;

interface GameApiImporterInterface
{
    public function import(string $username, string $password, ?string $proxy): ProductImportDTO;
}
