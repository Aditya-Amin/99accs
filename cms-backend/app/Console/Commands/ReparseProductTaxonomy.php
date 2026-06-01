<?php

namespace App\Console\Commands;

use App\Models\Product;
use App\Support\WooCommerceCategoryMapper;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Re-runs the WooCommerce category mapper on every product that still has its
 * `legacy_categories` JSON column populated. Useful when the mapper has been
 * upgraded since the original import (new skin-tag patterns, new account-type
 * keywords, etc.) — without it, those products keep their old taxonomy and
 * the new patterns produce zero hits even though the source data matches.
 *
 * Updates: skin_ids, region_ids, account_type_id, section_id, feature_badges
 * Syncs:   product_region, product_skin pivots
 *
 * Game-level FKs (game_id) are only filled if currently null, since the
 * mapper's game detection is heuristic and we don't want to overwrite
 * manual admin corrections.
 *
 * Idempotent — re-running with the same data is a no-op.
 */
class ReparseProductTaxonomy extends Command
{
    protected $signature   = 'products:reparse-taxonomy
        {--dry-run : Show what would change without writing}
        {--only-missing-skin : Only reprocess products whose skin_ids is null}';

    protected $description = 'Re-detect game / region / skin / account-type from legacy_categories on imported products';

    public function handle(): int
    {
        $dryRun       = (bool) $this->option('dry-run');
        $onlyMissing  = (bool) $this->option('only-missing-skin');

        $query = Product::whereNotNull('legacy_categories');
        if ($onlyMissing) {
            $query->whereNull('skin_ids');
        }

        $total = (clone $query)->count();

        if ($total === 0) {
            $this->info('No products with legacy_categories to reprocess.');
            return self::SUCCESS;
        }

        $this->info(($dryRun ? '[DRY RUN] ' : '') . "Re-parsing taxonomy for {$total} products...");

        $mapper = new WooCommerceCategoryMapper();
        $stats  = [
            'updated_skin_ids'    => 0,
            'updated_region_ids'  => 0,
            'updated_account'     => 0,
            'updated_section'     => 0,
            'updated_badges'      => 0,
            'new_skins_created'   => 0,
            'new_regions_created' => 0,
        ];
        $autoCreatedSeen = [];

        $bar = $this->output->createProgressBar($total);
        $bar->start();

        $query
            ->select(['id', 'name', 'sku', 'legacy_categories', 'game_id', 'account_type_id', 'section_id', 'region_ids', 'skin_ids', 'feature_badges'])
            ->orderBy('id')
            ->chunk(200, function ($products) use ($mapper, $dryRun, &$stats, &$autoCreatedSeen, $bar) {
                foreach ($products as $product) {
                    $cats = is_array($product->legacy_categories)
                        ? implode(', ', $product->legacy_categories)
                        : (string) $product->legacy_categories;

                    if ($cats === '') {
                        $bar->advance();
                        continue;
                    }

                    $parsed = $mapper->parse($cats, $product->name ?? '', $product->sku ?? '');

                    foreach ($parsed['_auto_created'] as $tag) {
                        $autoCreatedSeen[$tag] = true;
                    }

                    $newRegionIds = $parsed['region_ids'] ?: [];
                    $newSkinIds   = $parsed['skin_ids']   ?: [];

                    $regionChanged  = $this->idsDiffer($product->region_ids, $newRegionIds);
                    $skinChanged    = $this->idsDiffer($product->skin_ids,   $newSkinIds);
                    $accountChanged = $product->account_type_id !== $parsed['account_type_id'] && $parsed['account_type_id'] !== null;
                    $sectionChanged = $product->section_id      !== $parsed['section_id']      && $parsed['section_id']      !== null;
                    $badgesChanged  = json_encode($product->feature_badges ?? []) !== json_encode($parsed['feature_badges'] ?: []);

                    if (! $dryRun && ($regionChanged || $skinChanged || $accountChanged || $sectionChanged || $badgesChanged)) {
                        DB::transaction(function () use ($product, $parsed, $newRegionIds, $newSkinIds) {
                            // Only set game_id when product currently has none — don't override
                            // manual corrections that may have already been done in admin.
                            $updates = [
                                'region_ids'     => $newRegionIds ?: null,
                                'skin_ids'       => $newSkinIds   ?: null,
                                'feature_badges' => $parsed['feature_badges'] ?: null,
                            ];
                            if ($product->game_id === null && $parsed['game_id'] !== null) {
                                $updates['game_id'] = $parsed['game_id'];
                            }
                            if ($parsed['account_type_id'] !== null) {
                                $updates['account_type_id'] = $parsed['account_type_id'];
                            }
                            if ($parsed['section_id'] !== null) {
                                $updates['section_id'] = $parsed['section_id'];
                            }
                            $product->updateQuietly($updates);

                            $product->regions()->sync($newRegionIds);
                            $product->skinTags()->sync($newSkinIds);
                        });
                    }

                    if ($regionChanged)  $stats['updated_region_ids']++;
                    if ($skinChanged)    $stats['updated_skin_ids']++;
                    if ($accountChanged) $stats['updated_account']++;
                    if ($sectionChanged) $stats['updated_section']++;
                    if ($badgesChanged)  $stats['updated_badges']++;

                    $bar->advance();
                }
            });

        $bar->finish();
        $this->newLine(2);

        foreach ($autoCreatedSeen as $tag => $_) {
            if (str_starts_with($tag, 'skin:'))   $stats['new_skins_created']++;
            if (str_starts_with($tag, 'region:')) $stats['new_regions_created']++;
        }

        $this->table(
            ['Metric', 'Count'],
            [
                ['Products with skin_ids changed',    $stats['updated_skin_ids']],
                ['Products with region_ids changed',  $stats['updated_region_ids']],
                ['Products with account_type changed', $stats['updated_account']],
                ['Products with section changed',     $stats['updated_section']],
                ['Products with feature_badges changed', $stats['updated_badges']],
                ['New skin taxonomy terms created',   $stats['new_skins_created']],
                ['New region taxonomy terms created', $stats['new_regions_created']],
            ],
        );

        if ($autoCreatedSeen) {
            $this->newLine();
            $this->info('Auto-created taxonomy terms:');
            foreach (array_keys($autoCreatedSeen) as $tag) {
                $this->line('  - ' . $tag);
            }
        }

        if ($dryRun) {
            $this->warn('Dry run — no changes written. Re-run without --dry-run to apply.');
        } else {
            $this->info('Done. Reload Filament to see the new taxonomy.');
        }

        return self::SUCCESS;
    }

    /**
     * Two id arrays differ if they contain different values, ignoring order/dupes.
     */
    private function idsDiffer(mixed $current, array $next): bool
    {
        $cur = is_array($current) ? $current : (json_decode((string) ($current ?? ''), true) ?: []);
        sort($cur);
        sort($next);
        return $cur !== $next;
    }
}
