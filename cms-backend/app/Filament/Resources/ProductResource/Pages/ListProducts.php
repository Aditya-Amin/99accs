<?php

namespace App\Filament\Resources\ProductResource\Pages;

use App\Jobs\ImportWooCommerceProducts;
use App\Filament\Resources\ProductResource;
use Filament\Actions;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;

class ListProducts extends ListRecords
{
    protected static string $resource = ProductResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),

            Actions\Action::make('importWcProducts')
                ->label('Import WooCommerce Products')
                ->icon('heroicon-o-arrow-up-tray')
                ->color('gray')
                ->modalHeading('Import WooCommerce Products')
                ->modalDescription('Upload the WooCommerce product export CSV. Everything else — slugs, categories, regions, badges, SEO meta, images — is extracted from this single file. Products already imported (matched by legacy ID) are updated in place.')
                ->modalSubmitActionLabel('Queue Import')
                ->form([
                    FileUpload::make('products_csv')
                        ->label('WooCommerce Products CSV')
                        ->disk('local')
                        ->directory('imports/pending')
                        ->acceptedFileTypes(['text/csv', 'text/plain', 'application/csv', 'application/vnd.ms-excel', 'application/octet-stream'])
                        ->maxSize(102400) // 100 MB
                        ->required()
                        ->helperText('Export from WP admin → WooCommerce → Products → Export button → "Generate CSV".'),

                    Toggle::make('queue_images')
                        ->label('Download product images')
                        ->default(true)
                        ->helperText('Off = metadata only (fast). On = queue thousands of image fetches in the background.'),
                ])
                ->action(function (array $data): void {
                    $productsPath = $data['products_csv'];
                    $queueImages  = (bool) ($data['queue_images'] ?? true);

                    // slugsStoragePath is intentionally null — slugs are generated from
                    // product names directly inside the importer.
                    ImportWooCommerceProducts::dispatch($productsPath, null, $queueImages)
                        ->onQueue('imports');

                    Notification::make()
                        ->title('Product import queued')
                        ->body('Make sure a queue worker is running: php artisan queue:work --queue=imports,imports-media --memory=2048')
                        ->success()
                        ->send();
                }),
        ];
    }
}
