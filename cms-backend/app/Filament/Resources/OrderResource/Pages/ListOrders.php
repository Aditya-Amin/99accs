<?php

namespace App\Filament\Resources\OrderResource\Pages;

use App\Jobs\ImportWooCommerceOrders;
use App\Filament\Resources\OrderResource;
use Filament\Actions;
use Filament\Forms\Components\FileUpload;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;

class ListOrders extends ListRecords
{
    protected static string $resource = OrderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),

            Actions\Action::make('importWcOrders')
                ->label('Import WooCommerce Orders')
                ->icon('heroicon-o-arrow-up-tray')
                ->color('gray')
                ->modalHeading('Import WooCommerce Orders CSV')
                ->modalDescription('Upload the CSV exported from WooCommerce → Orders. Orders already in the database (matched by WC order ID) are skipped automatically. The import runs in the background — you can close this page once queued.')
                ->modalSubmitActionLabel('Queue Import')
                ->form([
                    FileUpload::make('csv_file')
                        ->label('WooCommerce Orders CSV')
                        ->disk('local')
                        ->directory('imports/pending')
                        ->acceptedFileTypes(['text/csv', 'text/plain', 'application/csv', 'application/vnd.ms-excel', 'application/octet-stream'])
                        ->maxSize(51200) // 50 MB
                        ->required()
                        ->helperText('Exported from WooCommerce → Orders → Export. Max 50 MB.'),
                ])
                ->action(function (array $data): void {
                    $storagePath = $data['csv_file'];

                    ImportWooCommerceOrders::dispatch($storagePath)
                        ->onQueue('imports');

                    Notification::make()
                        ->title('Import queued')
                        ->body('WooCommerce orders are being imported in the background. Run `php artisan queue:work --queue=imports` if the worker is not already running.')
                        ->success()
                        ->send();
                }),
        ];
    }
}
