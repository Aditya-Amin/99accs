<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Account types are per-game (the table has a game_id), but the original
 * migration made `slug` GLOBALLY unique — so "verified" could exist for only
 * ONE game. That forced the importer/mapper to borrow another game's account
 * type across games (e.g. a Fortnite product reusing Valorant's "verified").
 *
 * This aligns account_types with the `sections` table, which correctly uses a
 * composite unique on (game_id, slug). After this, each game can have its own
 * "verified" / "standard" / "inactive_exclusive" rows.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('account_types', function (Blueprint $table) {
            $table->dropUnique('account_types_slug_unique');
            $table->unique(['game_id', 'slug']);
        });
    }

    public function down(): void
    {
        // Reversible only if no duplicate slugs exist across games. If multiple
        // games now share a slug (the whole point of this change), the global
        // unique cannot be restored — drop the composite and leave slug indexed.
        Schema::table('account_types', function (Blueprint $table) {
            $table->dropUnique('account_types_game_id_slug_unique');
        });

        $hasDuplicateSlugs = \Illuminate\Support\Facades\DB::table('account_types')
            ->select('slug')
            ->groupBy('slug')
            ->havingRaw('COUNT(*) > 1')
            ->exists();

        Schema::table('account_types', function (Blueprint $table) use ($hasDuplicateSlugs) {
            if ($hasDuplicateSlugs) {
                $table->index('slug');           // can't be unique anymore
            } else {
                $table->unique('slug');
            }
        });
    }
};
