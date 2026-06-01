<?php

namespace App\Jobs;

use App\Models\Product;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Spatie\MediaLibrary\MediaCollections\Exceptions\FileCannotBeAdded;

class DownloadProductImages implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 300; // 5 minutes per product (multiple images)
    public int $tries   = 3;
    public int $backoff = 60;  // 1 minute between retries (transient WP host errors)

    /**
     * @param int    $productId  Our products.id
     * @param string $imagesCsv  Comma-separated URLs from WC export's `Images` column
     */
    public function __construct(
        public readonly int    $productId,
        public readonly string $imagesCsv,
    ) {}

    public function handle(): void
    {
        $product = Product::find($this->productId);
        if (! $product) {
            Log::warning("DownloadProductImages: product {$this->productId} not found (deleted?)");
            return;
        }

        $urls = array_values(array_filter(array_map('trim', explode(',', $this->imagesCsv))));
        if (empty($urls)) {
            return;
        }

        // Track which source_urls we already have so re-runs are idempotent
        $existingSources = $product
            ->getMedia('product_featured_image')
            ->merge($product->getMedia('product_gallery'))
            ->pluck('custom_properties.source_url')
            ->filter()
            ->all();

        foreach ($urls as $i => $url) {
            if (in_array($url, $existingSources, true)) {
                continue; // Already downloaded
            }

            // First image → featured, rest → gallery
            $collection = $i === 0 && empty($existingSources)
                ? 'product_featured_image'
                : 'product_gallery';

            try {
                $product->addMediaFromUrl($url)
                    ->withCustomProperties(['source_url' => $url])
                    ->toMediaCollection($collection);
            } catch (FileCannotBeAdded $e) {
                // 404, blocked UA, etc. — skip but log so we can audit later
                Log::warning("DownloadProductImages: failed url={$url} product={$this->productId} — {$e->getMessage()}");
            } catch (\Throwable $e) {
                Log::error("DownloadProductImages: unexpected error url={$url} product={$this->productId} — {$e->getMessage()}");
            }
        }

        // Flip has_gallery if we got more than one image
        $totalImages = $product->getMedia('product_featured_image')->count()
                     + $product->getMedia('product_gallery')->count();

        if ($totalImages > 1 && ! $product->has_gallery) {
            $product->updateQuietly(['has_gallery' => true]);
        }
    }
}
