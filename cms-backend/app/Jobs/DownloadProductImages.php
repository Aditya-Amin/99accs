<?php

namespace App\Jobs;

use App\Models\Product;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

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
                // Download ourselves with a browser-like User-Agent + Referer.
                // Spatie's addMediaFromUrl() sends a generic UA that WAFs/CDNs
                // (Cloudflare, hotlink protection) on the source host commonly
                // block — yielding 403/406 and zero images. A real UA + a
                // same-origin Referer gets past the typical bot filters.
                $referer  = $this->originOf($url);
                $response = Http::withHeaders([
                    'User-Agent'      => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36',
                    'Accept'          => 'image/avif,image/webp,image/apng,image/png,image/jpeg,image/svg+xml,*/*;q=0.8',
                    'Accept-Language' => 'en-US,en;q=0.9',
                    'Referer'         => $referer,
                ])
                    ->timeout(60)
                    ->retry(2, 2000, throw: false)
                    ->get($url);

                if (! $response->successful() || $response->body() === '') {
                    Log::warning("DownloadProductImages: HTTP {$response->status()} (".strlen($response->body())." bytes) url={$url} product={$this->productId}");
                    continue;
                }

                $product->addMediaFromString($response->body())
                    ->usingFileName($this->fileNameFromUrl($url))
                    ->withCustomProperties(['source_url' => $url])
                    ->toMediaCollection($collection);
            } catch (\Throwable $e) {
                Log::warning("DownloadProductImages: failed url={$url} product={$this->productId} — {$e->getMessage()}");
            }
        }

        // Flip has_gallery if we got more than one image
        $totalImages = $product->getMedia('product_featured_image')->count()
                     + $product->getMedia('product_gallery')->count();

        if ($totalImages > 1 && ! $product->has_gallery) {
            $product->updateQuietly(['has_gallery' => true]);
        }
    }

    /** scheme://host of a URL, used as a same-origin Referer to satisfy hotlink protection. */
    private function originOf(string $url): string
    {
        $parts = parse_url($url);
        $scheme = $parts['scheme'] ?? 'https';
        $host   = $parts['host'] ?? '';
        return $host !== '' ? "{$scheme}://{$host}/" : $url;
    }

    /** Derive a clean, extension-preserving file name from the source URL. */
    private function fileNameFromUrl(string $url): string
    {
        $name = basename(parse_url($url, PHP_URL_PATH) ?: '');
        $name = urldecode($name);

        if ($name === '' || ! str_contains($name, '.')) {
            return 'image-' . substr(md5($url), 0, 10) . '.jpg';
        }

        // Sanitise but keep the extension intact.
        $ext  = Str::afterLast($name, '.');
        $base = Str::slug(Str::beforeLast($name, '.')) ?: ('image-' . substr(md5($url), 0, 10));

        return "{$base}.{$ext}";
    }
}
