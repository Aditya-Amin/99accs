<?php

namespace App\Console\Commands;

use App\Models\Product;
use Illuminate\Console\Command;

/**
 * Bulk-publishes imported WooCommerce products.
 *
 * The import job sets is_visible=false so admins can review before going live.
 * Run this once to flip all imported products that have the minimum required
 * taxonomy (game_id) to is_visible=true.
 *
 * Safe to re-run — products already published are left untouched.
 * Products without a game_id are skipped and listed so you can investigate.
 */
class PublishImportedProducts extends Command
{
    protected $signature = 'products:publish-imported
        {--all : Publish ALL imported products, even those missing game_id}
        {--dry-run : Show counts without writing}';

    protected $description = 'Set is_visible=true on all imported products that have a game assigned';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $all    = (bool) $this->option('all');

        $base = Product::where('source_provider', 'woocommerce')
            ->where('is_visible', false);

        $withGame    = (clone $base)->whereNotNull('game_id');
        $withoutGame = (clone $base)->whereNull('game_id');

        $countWith    = $withGame->count();
        $countWithout = $withoutGame->count();

        $this->info(($dryRun ? '[DRY RUN] ' : '') . "Products ready to publish (has game_id): {$countWith}");
        $this->warn("Products still missing game_id (skipped):           {$countWithout}");

        if ($countWith === 0 && ! $all) {
            $this->info('Nothing to publish.');
            return self::SUCCESS;
        }

        if (! $dryRun) {
            $published = $withGame->update(['is_visible' => true]);
            $this->info("Published {$published} products.");

            if ($all && $countWithout > 0) {
                $forcedPublished = $withoutGame->update(['is_visible' => true]);
                $this->warn("Force-published {$forcedPublished} products without game_id (--all flag).");
            }
        }

        if ($countWithout > 0 && ! $all) {
            $this->newLine();
            $this->warn("Run 'php artisan products:reparse-taxonomy' first to assign game_id to the remaining {$countWithout} products,");
            $this->warn("then re-run this command. Or use --all to publish everything regardless.");
        }

        if ($dryRun) {
            $this->warn('Dry run — no changes written.');
        }

        return self::SUCCESS;
    }
}
