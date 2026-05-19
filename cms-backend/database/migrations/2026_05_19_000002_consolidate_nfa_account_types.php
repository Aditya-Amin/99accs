<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $fortniteId = DB::table('games')->where('slug', 'fortnite')->value('id');
        $now        = now();

        // Find or create the consolidated 'nfa' account_type
        $nfaId = DB::table('account_types')->where('slug', 'nfa')->value('id');

        if (! $nfaId) {
            $existing = DB::table('account_types')->where('slug', 'nfa_random')->first();
            if ($existing) {
                DB::table('account_types')->where('id', $existing->id)->update([
                    'slug'       => 'nfa',
                    'name'       => 'NFA',
                    'updated_at' => $now,
                ]);
                $nfaId = $existing->id;
            } else {
                $nfaId = DB::table('account_types')->insertGetId([
                    'name'          => 'NFA',
                    'slug'          => 'nfa',
                    'game_id'       => $fortniteId,
                    'detail_layout' => 'simple_two',
                    'sort_order'    => 1,
                    'created_at'    => $now,
                    'updated_at'    => $now,
                ]);
            }
        }

        // Migrate any products on the legacy slugs over to the consolidated row,
        // then delete the old account_types rows.
        $oldIds = DB::table('account_types')
            ->whereIn('slug', ['nfa_random', 'nfa_guaranteed'])
            ->pluck('id');

        if ($oldIds->isNotEmpty()) {
            DB::table('products')
                ->whereIn('account_type_id', $oldIds)
                ->update(['account_type_id' => $nfaId]);

            DB::table('account_types')->whereIn('id', $oldIds)->delete();
        }
    }

    public function down(): void
    {
        $fortniteId = DB::table('games')->where('slug', 'fortnite')->value('id');
        $now        = now();

        $nfa = DB::table('account_types')->where('slug', 'nfa')->first();
        if (! $nfa) {
            return;
        }

        DB::table('account_types')->where('id', $nfa->id)->update([
            'slug'       => 'nfa_random',
            'name'       => 'NFA Random Skins',
            'updated_at' => $now,
        ]);

        DB::table('account_types')->insert([
            'name'          => 'NFA Guaranteed Skins',
            'slug'          => 'nfa_guaranteed',
            'game_id'       => $fortniteId,
            'detail_layout' => 'simple_two',
            'sort_order'    => 2,
            'created_at'    => $now,
            'updated_at'    => $now,
        ]);
    }
};
