<?php

namespace App\Jobs;

use App\Models\Product;
use App\Support\WooCommerceCategoryMapper;
use App\Support\WooCommerceDescriptionParser;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ImportWooCommerceProducts implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 7200;
    public int $tries   = 1;

    /**
     * @param string      $csvStoragePath    Path to WC product export CSV under storage/app
     * @param string|null $slugsStoragePath  Optional path to slugs CSV (ID, post_name, post_title from wp post list)
     * @param bool        $queueImages       Whether to dispatch DownloadProductImages jobs (default true)
     */
    public function __construct(
        private readonly string  $csvStoragePath,
        private readonly ?string $slugsStoragePath = null,
        private readonly bool    $queueImages      = true,
    ) {}

    public function handle(): void
    {
        $fullPath = Storage::path($this->csvStoragePath);
        if (! file_exists($fullPath)) {
            Log::error("ImportWooCommerceProducts: file not found at {$this->csvStoragePath}");
            return;
        }

        // Pre-load the WP slug map if a slugs CSV was provided
        $slugMap = $this->loadSlugMap();

        // Build parsers once per job — taxonomy lookups are cached internally
        $categoryMapper    = new WooCommerceCategoryMapper();
        $descriptionParser = new WooCommerceDescriptionParser();

        $handle = fopen($fullPath, 'r');
        if ($handle === false) {
            Log::error("ImportWooCommerceProducts: cannot open {$this->csvStoragePath}");
            return;
        }

        $headers   = null;
        $chunk     = [];
        $chunkSize = 100;
        $totals    = ['imported' => 0, 'updated' => 0, 'skipped' => 0, 'failed' => 0];

        while (($row = fgetcsv($handle, 0, ',', '"', '\\')) !== false) {
            if ($headers === null) {
                // Strip UTF-8 BOM from first column header
                $headers = array_map(static fn($h) => ltrim(trim($h), "\xEF\xBB\xBF"), $row);
                continue;
            }

            // Pad sparse rows so array_combine doesn't error
            if (count($row) < count($headers)) {
                $row = array_pad($row, count($headers), '');
            } elseif (count($row) > count($headers)) {
                $row = array_slice($row, 0, count($headers));
            }

            $chunk[] = array_combine($headers, $row);

            if (count($chunk) >= $chunkSize) {
                $this->mergeCounters($totals, $this->processChunk($chunk, $slugMap, $categoryMapper, $descriptionParser));
                $chunk = [];
            }
        }

        if (! empty($chunk)) {
            $this->mergeCounters($totals, $this->processChunk($chunk, $slugMap, $categoryMapper, $descriptionParser));
        }

        fclose($handle);
        Storage::delete($this->csvStoragePath);
        // Intentionally keep the slugs CSV — it's a reusable reference file
        // (read in loadSlugMap() each run). Only delete the transient products upload.

        Log::info(sprintf(
            'ImportWooCommerceProducts complete — imported=%d, updated=%d, skipped=%d, failed=%d',
            $totals['imported'], $totals['updated'], $totals['skipped'], $totals['failed']
        ));
    }

    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Load the (WC ID → WP slug) map from the optional slugs CSV.
     * Expected columns: ID, post_name, post_title (from `wp post list`).
     */
    private function loadSlugMap(): array
    {
        if (! $this->slugsStoragePath) {
            return [];
        }

        $path = Storage::path($this->slugsStoragePath);
        if (! file_exists($path)) {
            Log::warning("ImportWooCommerceProducts: slugs CSV not found at {$this->slugsStoragePath} — falling back to Str::slug()");
            return [];
        }

        $handle  = fopen($path, 'r');
        $headers = fgetcsv($handle);
        $headers = array_map(static fn($h) => ltrim(trim($h), "\xEF\xBB\xBF"), $headers);

        $idIdx   = array_search('ID', $headers, true);
        $slugIdx = array_search('post_name', $headers, true);

        if ($idIdx === false || $slugIdx === false) {
            Log::warning('ImportWooCommerceProducts: slugs CSV missing ID/post_name columns — falling back to Str::slug()');
            fclose($handle);
            return [];
        }

        $map = [];
        while (($row = fgetcsv($handle)) !== false) {
            $id   = trim($row[$idIdx] ?? '');
            $slug = trim($row[$slugIdx] ?? '');
            if ($id !== '' && $slug !== '') {
                $map[$id] = $slug;
            }
        }
        fclose($handle);

        Log::info('ImportWooCommerceProducts: loaded ' . count($map) . ' real slugs from CSV');
        return $map;
    }

    private function processChunk(
        array $rows,
        array $slugMap,
        WooCommerceCategoryMapper $categoryMapper,
        WooCommerceDescriptionParser $descriptionParser,
    ): array {
        $counts = ['imported' => 0, 'updated' => 0, 'skipped' => 0, 'failed' => 0];

        DB::transaction(function () use ($rows, $slugMap, $categoryMapper, $descriptionParser, &$counts) {
            foreach ($rows as $data) {
                $result = $this->processRow($data, $slugMap, $categoryMapper, $descriptionParser);
                $counts[$result]++;
            }
        });

        return $counts;
    }

    private function processRow(
        array $data,
        array $slugMap,
        WooCommerceCategoryMapper $categoryMapper,
        WooCommerceDescriptionParser $descriptionParser,
    ): string {
        $legacyId = trim($data['ID'] ?? '');
        if ($legacyId === '') {
            return 'skipped';
        }

        try {
            $type = trim($data['Type'] ?? 'simple');
            $name = trim($data['Name'] ?? '');

            // Variation rows can have empty Name in some exports — use parent + attributes
            if ($name === '' && $type === 'variation') {
                $name = 'Variation of #' . trim($data['Parent'] ?? '');
            }

            if ($name === '') {
                return 'skipped';
            }

            // ── Slug resolution ───────────────────────────────────────────────
            // Priority order:
            //   1. WP slug from the slugs CSV (preserves SEO) — IF it's clean ASCII
            //   2. Str::slug(name) + '-' + legacy_id (clean, guaranteed unique)
            //
            // Some WP slugs come URL-encoded for emoji titles, e.g.
            //   "150-skins-inactive-na-%f0%9f%87%ba%f0%9f%87%b8"  (the 🇺🇸 flag)
            // Those are ugly and indistinct in browsers/social shares — we regenerate
            // them from the title instead. SEO continuity loss is minimal because
            // emoji-encoded URLs never ranked well anyway and were rarely shared.
            $wpSlug = $slugMap[$legacyId] ?? null;
            $slug   = ($wpSlug && ! preg_match('/%[0-9a-f]{2}/i', $wpSlug))
                ? $wpSlug
                : (Str::slug($name) ?: 'product') . '-' . $legacyId;

            // ── Pricing ───────────────────────────────────────────────────────
            $regularPrice = $this->parsePrice($data['Regular price'] ?? '');
            $salePrice    = $this->parsePrice($data['Sale price']    ?? '');
            $price        = $salePrice ?? $regularPrice ?? 0;

            // ── Stock ─────────────────────────────────────────────────────────
            $stockRaw  = trim($data['Stock'] ?? '');
            $stockQty  = $stockRaw === '' ? 0 : max(0, (int) $stockRaw);

            // ── Categories: keep raw as legacy_categories + parse into taxonomy IDs.
            // We pass name and SKU as fallback context so region detection still works
            // for products whose categories omit region (e.g. "Mail Access | AP 🔰" with no Valorant AP category).
            $categories     = trim($data['Categories'] ?? '');
            $categoriesJson = $categories === ''
                ? null
                : array_map('trim', explode(',', $categories));
            $taxonomy    = $categoryMapper->parse(
                $categories,
                $name,
                trim($data['SKU'] ?? ''),
            );
            $autoCreated = $taxonomy['_auto_created'] ?? [];
            unset($taxonomy['_auto_created']);

            if ($autoCreated) {
                Log::info("ImportWooCommerceProducts: auto-created taxonomy for product {$legacyId} — " . implode(', ', $autoCreated));
            }

            // ── Description: parse HTML into structured fields ────────────────
            $parsed = $descriptionParser->parse(trim($data['Description'] ?? ''));

            // ── SEO (clean Yoast placeholders) ────────────────────────────────
            $metaTitle = $this->cleanYoastPlaceholders($data['Meta: _yoast_wpseo_title'] ?? '', $name);
            $metaDesc  = $this->cleanYoastPlaceholders($data['Meta: _yoast_wpseo_metadesc'] ?? '', $name);
            $metaKeys  = trim($data['Meta: _yoast_wpseo_metakeywords'] ?? '') ?: null;
            $isCorner  = trim($data['Meta: _yoast_wpseo_is_cornerstone'] ?? '') === '1';

            $regionIds = $taxonomy['region_ids'] ?: [];
            $skinIds   = $taxonomy['skin_ids']   ?: [];

            // ── Persist (upsert by legacy_id, idempotent re-imports) ──────────
            $attributes = [
                'legacy_id'            => $legacyId,
                'legacy_categories'    => $categoriesJson,
                // Parsed taxonomy — null when no confident match
                'game_id'              => $taxonomy['game_id'],
                'account_type_id'      => $taxonomy['account_type_id'],
                'section_id'           => $taxonomy['section_id'],
                // Denormalized JSON copies kept in sync with pivot tables below
                'region_ids'           => $regionIds ?: null,
                'skin_ids'             => $skinIds   ?: null,
                'feature_badges'       => $taxonomy['feature_badges'] ?: null,
                // Core
                'name'                 => $name,
                'slug'                 => $slug,
                'sku'                  => trim($data['SKU'] ?? '') ?: null,
                'description'          => $parsed['description'],
                // Structured fields left null — admin curates manually
                'description_sections' => null,
                'highlights'           => null,
                'faq_items'            => null,
                // Pricing
                'price'                => $price,
                'regular_price'        => $regularPrice ?? $price,
                'stock_qty'            => $stockQty,
                'is_visible'           => false,
                // SEO
                'meta_title'           => $metaTitle,
                'meta_description'     => $metaDesc,
                'meta_keywords'        => $metaKeys,
                'is_cornerstone'       => $isCorner,
                'source_provider'      => 'woocommerce',
                'external_id'          => $legacyId,
                'synced_at'            => now(),
            ];

            $existing = Product::where('legacy_id', $legacyId)->first();
            $isNew    = $existing === null;
            $product  = $existing ?? new Product();
            $product->fill($attributes);

            // Preserve created_at for re-imports
            if ($isNew) {
                $product->created_at = $this->parseDate($data['Date sale price starts'] ?? null) ?? now();
            }
            $product->save();

            // ── Sync M:N pivot tables ─────────────────────────────────────────
            // product_region: sync region IDs (replaces any prior regions)
            $product->regions()->sync($regionIds);
            // product_skin: sync skin tag IDs
            $product->skinTags()->sync($skinIds);

            // ── Queue per-product image download ─────────────────────────────
            $imagesCsv = trim($data['Images'] ?? '');
            if ($this->queueImages && $imagesCsv !== '') {
                DownloadProductImages::dispatch($product->id, $imagesCsv)
                    ->onQueue('imports-media');
            }

            return $isNew ? 'imported' : 'updated';

        } catch (\Throwable $e) {
            Log::error("ImportWooCommerceProducts: product {$legacyId} failed — {$e->getMessage()}");
            return 'failed';
        }
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function parsePrice(string $raw): ?float
    {
        $raw = trim($raw);
        if ($raw === '') return null;
        // WC exports decimals as plain numbers — but strip currency symbols just in case
        $clean = preg_replace('/[^0-9.\-]/', '', $raw);
        return $clean === '' ? null : (float) $clean;
    }

    private function parseDate(?string $raw): ?Carbon
    {
        if (! $raw || trim($raw) === '') return null;
        try {
            $date = Carbon::parse($raw);
            // WP stores 0000-00-00 for unset dates; Carbon parses these as year -0001
            // which MySQL rejects with error 1292.
            return $date->year > 1900 ? $date : null;
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * Yoast titles/descriptions contain placeholders like %%title%%, %%page%%, %%sep%%,
     * %%sitename%% — these are rendered server-side by WP. We substitute %%title%% with
     * the product name and strip the rest, since Next.js doesn't process them.
     */
    private function cleanYoastPlaceholders(string $raw, string $productName): ?string
    {
        $raw = trim($raw);
        if ($raw === '') return null;

        $replaced = strtr($raw, [
            '%%title%%'    => $productName,
            '%%page%%'     => '',
            '%%sep%%'      => '|',
            '%%sitename%%' => '',
        ]);

        // Strip any other %%placeholder%% we didn't handle
        $replaced = preg_replace('/%%[a-z_]+%%/i', '', $replaced);

        // Collapse multiple spaces / orphan separators
        $replaced = preg_replace('/\s*\|\s*\|?\s*/u', ' | ', $replaced);
        $replaced = trim(preg_replace('/\s+/u', ' ', $replaced), " \t\n\r\0\x0B|");

        return $replaced === '' ? null : $replaced;
    }

    private function mergeCounters(array &$totals, array $chunk): void
    {
        foreach ($chunk as $key => $val) {
            $totals[$key] = ($totals[$key] ?? 0) + $val;
        }
    }
}
