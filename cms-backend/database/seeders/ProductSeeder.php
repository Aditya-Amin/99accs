<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('Seeding products from Next.js mock data...');

        $mockPath = base_path('../99accs-app/mocks/products');

        $files = ['valorant', 'fortnite', 'legends'];
        $all   = [];

        foreach ($files as $file) {
            $path = "{$mockPath}/{$file}.json";
            if (! file_exists($path)) {
                $this->command->warn("Mock file not found: {$path} — skipping.");
                continue;
            }
            $items = json_decode(file_get_contents($path), true);
            $all   = array_merge($all, $items);
        }

        if (empty($all)) {
            $this->command->error('No mock data found. Make sure 99accs-app/ is next to cms-backend/.');
            return;
        }

        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        DB::table('product_skin')->truncate();
        DB::table('products')->truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1');

        // ── Build taxonomy lookup maps ─────────────────────────────────────────
        $games        = DB::table('games')->pluck('id', 'slug');
        $accountTypes = DB::table('account_types')->pluck('id', 'slug');

        // Normalize legacy slugs from mock JSON that have been consolidated
        $accountTypeAliases = [
            'nfa_random'     => 'nfa',
            'nfa_guaranteed' => 'nfa',
        ];

        // Sections keyed by "game_slug.section_slug"
        $sections = DB::table('sections')
            ->join('games', 'sections.game_id', '=', 'games.id')
            ->select('sections.id', 'sections.slug as section_slug', 'games.slug as game_slug')
            ->get()
            ->mapWithKeys(fn ($s) => ["{$s->game_slug}.{$s->section_slug}" => $s->id]);

        // ── Ensure a Region row exists for each (code, flag, class_modifier) in mock data
        $regionIdByCode = $this->upsertRegionsFromMock($all);

        // ── Insert products ────────────────────────────────────────────────────
        $rows = array_map(
            fn ($p) => $this->transform($p, $games, $accountTypes, $accountTypeAliases, $sections, $regionIdByCode),
            $all
        );

        foreach (array_chunk($rows, 20) as $chunk) {
            DB::table('products')->insert($chunk);
        }

        $this->command->info('Seeded ' . count($rows) . ' products.');
    }

    /**
     * Walk the mock data, ensure a regions row exists for each distinct
     * country code, and return [CODE => region_id].
     */
    private function upsertRegionsFromMock(array $products): array
    {
        $existing = DB::table('regions')->get()->keyBy(fn ($r) => strtoupper($r->code ?? ''));
        $now = now()->toDateTimeString();
        $byCode = [];

        foreach ($products as $p) {
            $code = strtoupper(trim($p['country']['code'] ?? ''));
            if (! $code) continue;
            if (isset($byCode[$code])) continue;

            $flag           = $p['country']['flag']           ?? null;
            $classModifier  = $p['country']['class_modifier'] ?? null;
            // Prefer the product's explicit `region` slug if present, else lowercased code
            $slug = $p['region'] ?? strtolower($code);

            if (isset($existing[$code])) {
                $rid = $existing[$code]->id;
                $updates = [];
                if (! $existing[$code]->flag && $flag)                       $updates['flag'] = $flag;
                if (! $existing[$code]->class_modifier && $classModifier)    $updates['class_modifier'] = $classModifier;
                if ($updates) DB::table('regions')->where('id', $rid)->update($updates);
                $byCode[$code] = $rid;
                continue;
            }

            // No region with that code yet — create one. Handle slug collision.
            $finalSlug = $slug; $i = 1;
            while (DB::table('regions')->where('slug', $finalSlug)->exists()) {
                $finalSlug = $slug . '-' . (++$i);
            }
            $byCode[$code] = DB::table('regions')->insertGetId([
                'name'           => $p['country']['name'] ?? $code,
                'slug'           => $finalSlug,
                'code'           => $code,
                'flag'           => $flag,
                'class_modifier' => $classModifier,
                'sort_order'     => 100,
                'created_at'     => $now,
                'updated_at'     => $now,
            ]);
        }

        return $byCode;
    }

    private function transform(
        array $p,
        \Illuminate\Support\Collection $games,
        \Illuminate\Support\Collection $accountTypes,
        array $accountTypeAliases,
        \Illuminate\Support\Collection $sections,
        array $regionIdByCode,
    ): array {
        $now = now()->toDateTimeString();

        $gameSectionKey  = "{$p['game']}.{$p['section']['slug']}";
        $accountTypeSlug = $accountTypeAliases[$p['account_type']] ?? $p['account_type'];
        $code            = strtoupper(trim($p['country']['code'] ?? ''));

        return [
            // Core
            'name'                   => $p['title'],
            'slug'                   => $p['slug'],
            'description'            => $p['description'] ?? '',
            'short_description'      => null,
            'is_visible'             => true,
            'sku'                    => null,

            // Pricing
            'price'                  => $p['price'],
            'price_max'              => $p['price_max'] ?? null,
            'compare_at_price'       => $p['old_price'] ?? null,
            'discount_percent'       => $p['discount_percent'] ?? null,
            'badge_icon'             => $p['badge_icon'] ?? null,

            // Inventory
            'stock_qty'              => $p['stock'],
            'min_quantity'           => $p['min_quantity'] ?? null,

            // Taxonomy FKs (region drives country badge + flag for the card)
            'game_id'                => $games[$p['game']] ?? null,
            'account_type_id'        => $accountTypes[$accountTypeSlug] ?? null,
            'section_id'             => $sections[$gameSectionKey] ?? null,
            'region_id'              => $regionIdByCode[$code] ?? null,

            // Denormalized skin M:N column — populated by syncDenormalized() when pivots are written
            'skin_ids'               => null,

            // Rank / gallery flag
            'rank'                   => $p['rank'] ?? null,
            'has_gallery'            => (int) ($p['has_gallery'] ?? false),

            // JSON — media
            'images'                 => json_encode($p['images'] ?? []),

            // JSON — game content
            'agents'                 => json_encode($p['agents'] ?? []),
            'skins'                  => json_encode($p['skins'] ?? []),
            'buddies'                => json_encode($p['buddies'] ?? []),
            'specs'                  => json_encode($p['specs'] ?? (object) []),
            'feature_badges'         => json_encode($p['categories'] ?? []),

            // Detail-only
            'agents_detailed'        => null,
            'agents_count'           => null,
            'profile_info'           => null,
            'skin_inventory'         => null,
            'skin_filters'           => null,
            'buddy_inventory'        => null,
            'account_level'          => null,
            'account_stats'          => null,
            'locker'                 => null,
            'seasons'                => null,
            'description_sections'   => null,
            'last_match_label'       => $p['last_match_label'] ?? null,
            'guarantee'              => null,

            // Sync
            'source_provider'        => null,
            'external_id'            => null,
            'synced_at'              => null,

            // Timestamps — convert ISO 8601 to MySQL datetime format
            'created_at'             => isset($p['created_at'])
                ? date('Y-m-d H:i:s', strtotime($p['created_at']))
                : $now,
            'updated_at'             => $now,
        ];
    }
}
