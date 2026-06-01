<?php

namespace App\Console\Commands;

use App\Models\Product;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * One-shot backfill that rebuilds the `product_region` and `product_skin`
 * pivot tables from the denormalized `region_ids` / `skin_ids` JSON columns
 * on products. Run this once after upgrading from the legacy importer that
 * only wrote the JSON columns.
 *
 * Idempotent: re-running with the same data is a no-op.
 */
class SyncProductTaxonomyPivots extends Command
{
    protected $signature   = 'products:sync-taxonomy-pivots {--dry-run : Show what would change without writing}';
    protected $description = 'Rebuild product_region / product_skin pivots from region_ids / skin_ids JSON columns';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $stats  = ['regions_synced' => 0, 'skins_synced' => 0, 'skipped' => 0];

        $total = Product::whereNotNull('region_ids')
            ->orWhereNotNull('skin_ids')
            ->count();

        if ($total === 0) {
            $this->info('No products with region_ids or skin_ids JSON — nothing to do.');
            return self::SUCCESS;
        }

        $this->info(($dryRun ? '[DRY RUN] ' : '') . "Backfilling pivots for {$total} products...");

        $bar = $this->output->createProgressBar($total);
        $bar->start();

        Product::query()
            ->where(function ($q) {
                $q->whereNotNull('region_ids')->orWhereNotNull('skin_ids');
            })
            ->select(['id', 'region_ids', 'skin_ids'])
            ->orderBy('id')
            ->chunk(200, function ($products) use ($dryRun, &$stats, $bar) {
                foreach ($products as $product) {
                    $regionIds = $this->normalizeIds($product->region_ids);
                    $skinIds   = $this->normalizeIds($product->skin_ids);

                    if ($dryRun) {
                        if ($regionIds) $stats['regions_synced']++;
                        if ($skinIds)   $stats['skins_synced']++;
                        $bar->advance();
                        continue;
                    }

                    DB::transaction(function () use ($product, $regionIds, $skinIds, &$stats) {
                        if ($regionIds) {
                            $product->regions()->sync($regionIds);
                            $stats['regions_synced']++;
                        }
                        if ($skinIds) {
                            $product->skinTags()->sync($skinIds);
                            $stats['skins_synced']++;
                        }
                    });

                    $bar->advance();
                }
            });

        $bar->finish();
        $this->newLine(2);

        $this->table(
            ['Metric', 'Count'],
            [
                ['Products with region_ids synced', $stats['regions_synced']],
                ['Products with skin_ids synced',   $stats['skins_synced']],
            ],
        );

        if ($dryRun) {
            $this->warn('Dry run — no changes written. Re-run without --dry-run to apply.');
        } else {
            $this->info('Done. Filament dropdowns will now show the imported regions/skin types.');
        }

        return self::SUCCESS;
    }

    /**
     * Cast a JSON column value (array | string | null) into a clean int[] for sync().
     */
    private function normalizeIds(mixed $raw): array
    {
        if ($raw === null || $raw === '') return [];

        $ids = is_array($raw) ? $raw : (json_decode((string) $raw, true) ?? []);

        return array_values(array_unique(array_filter(
            array_map('intval', $ids),
            static fn ($id) => $id > 0,
        )));
    }
}
