<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Sets up the Skins taxonomy with two skin-type terms and assigns
 * NFA Fortnite products to the correct type based on their skins JSON column.
 *
 * nfa_guaranteed → products that have named skins in the `skins` JSON column
 * nfa_random     → products with an empty / null skins JSON column
 *
 * Safe to re-run: truncates skins + product_skin before seeding.
 */
class SkinTaxonomyMigrationSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('Setting up Skins taxonomy (skin types)...');

        $fortniteId = DB::table('games')->where('slug', 'fortnite')->value('id');

        if (! $fortniteId) {
            $this->command->error('Fortnite game not found — run migrations first.');
            return;
        }

        // ── 1. Reset ──────────────────────────────────────────────────────────
        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        DB::table('product_skin')->truncate();
        DB::table('skins')->truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1');

        // Clear denormalized column
        DB::table('products')->update(['skin_ids' => null]);

        // ── 2. Seed the two skin type terms ───────────────────────────────────
        $guaranteedId = DB::table('skins')->insertGetId([
            'name'       => 'NFA Guaranteed Skins',
            'slug'       => 'nfa-guaranteed-skins',
            'game_id'    => $fortniteId,
            'sort_order' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $randomId = DB::table('skins')->insertGetId([
            'name'       => 'NFA Random Skins',
            'slug'       => 'nfa-random-skins',
            'game_id'    => $fortniteId,
            'sort_order' => 2,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->command->line("  Created: <fg=green>NFA Guaranteed Skins</> (#{$guaranteedId})");
        $this->command->line("  Created: <fg=green>NFA Random Skins</> (#{$randomId})");

        // ── 3. Assign NFA products to their skin type ─────────────────────────
        $atId = DB::table('account_types')->where('slug', 'nfa')->value('id');

        if (! $atId) {
            $this->command->error('NFA account type not found — run ProductSeeder first.');
            return;
        }

        $products = DB::table('products')
            ->where('account_type_id', $atId)
            ->get(['id', 'skins']);

        $guaranteedCount = 0;
        $randomCount     = 0;
        $pivotRows       = [];

        foreach ($products as $product) {
            $decoded    = json_decode($product->skins, true);
            $hasNamed   = is_array($decoded) && count($decoded) > 0;
            $skinTypeId = $hasNamed ? $guaranteedId : $randomId;

            $pivotRows[] = ['product_id' => $product->id, 'skin_id' => $skinTypeId];

            if ($hasNamed) {
                $guaranteedCount++;
            } else {
                $randomCount++;
            }
        }

        foreach (array_chunk($pivotRows, 50) as $chunk) {
            DB::table('product_skin')->insert($chunk);
        }

        $this->command->info("  Assigned: {$guaranteedCount} guaranteed, {$randomCount} random products.");

        // ── 4. Rebuild skin_ids JSON column ───────────────────────────────────
        $this->syncSkinIds();

        $this->command->info('Done.');
    }

    private function syncSkinIds(): void
    {
        DB::table('product_skin')
            ->select('product_id')
            ->distinct()
            ->get()
            ->each(function ($r) {
                $ids = DB::table('product_skin')
                    ->where('product_id', $r->product_id)
                    ->pluck('skin_id')
                    ->all();

                DB::table('products')
                    ->where('id', $r->product_id)
                    ->update(['skin_ids' => json_encode(array_values($ids))]);
            });

        $this->command->info('  skin_ids JSON column synced.');
    }
}
