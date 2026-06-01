<?php

namespace App\Filament\Resources\CustomerResource\Pages;

use App\Filament\Imports\WordPressUserImporter;
use App\Filament\Resources\CustomerResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListCustomers extends ListRecords
{
    protected static string $resource = CustomerResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),

            Actions\ImportAction::make()
                ->importer(WordPressUserImporter::class)
                ->label('Import WordPress Users')
                ->icon('heroicon-o-arrow-up-tray')
                ->color('gray')
                ->chunkSize(100)   // 100-row batches → dispatched as separate queue jobs
                ->maxRows(100000), // safety cap for 50K+ exports
        ];
    }
}
