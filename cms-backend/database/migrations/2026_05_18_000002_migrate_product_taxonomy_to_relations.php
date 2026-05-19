<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ── Step 1: add nullable FK columns ───────────────────────────────────
        Schema::table('products', function (Blueprint $table) {
            $table->unsignedBigInteger('game_id')->nullable()->after('type');
            $table->unsignedBigInteger('account_type_id')->nullable()->after('game_id');
            $table->unsignedBigInteger('region_id')->nullable()->after('account_type_id');
            $table->unsignedBigInteger('section_id')->nullable()->after('region_id');

            $table->foreign('game_id')->references('id')->on('games')->nullOnDelete();
            $table->foreign('account_type_id')->references('id')->on('account_types')->nullOnDelete();
            $table->foreign('region_id')->references('id')->on('regions')->nullOnDelete();
            $table->foreign('section_id')->references('id')->on('sections')->nullOnDelete();
        });

        // ── Step 2: data backfill ──────────────────────────────────────────────

        // Build slug→id lookup maps
        $games        = DB::table('games')->pluck('id', 'slug');
        $accountTypes = DB::table('account_types')->pluck('id', 'slug');
        $regions      = DB::table('regions')->pluck('id', 'slug');

        // Sections keyed by "game_slug.section_slug"
        $sections = DB::table('sections')
            ->join('games', 'sections.game_id', '=', 'games.id')
            ->select('sections.id', 'sections.slug as section_slug', 'games.slug as game_slug')
            ->get()
            ->mapWithKeys(fn ($s) => ["{$s->game_slug}.{$s->section_slug}" => $s->id]);

        // Iterate products in chunks to avoid memory issues
        DB::table('products')
            ->select('id', 'game', 'account_type', 'region', 'section_slug')
            ->orderBy('id')
            ->chunk(200, function ($products) use ($games, $accountTypes, $regions, $sections) {
                foreach ($products as $p) {
                    $gameId        = isset($p->game)         ? ($games[$p->game]               ?? null) : null;
                    $accountTypeId = isset($p->account_type) ? ($accountTypes[$p->account_type] ?? null) : null;
                    $regionId      = isset($p->region)       ? ($regions[$p->region]             ?? null) : null;
                    $sectionKey    = $p->game && $p->section_slug ? "{$p->game}.{$p->section_slug}" : null;
                    $sectionId     = $sectionKey ? ($sections[$sectionKey] ?? null) : null;

                    DB::table('products')->where('id', $p->id)->update([
                        'game_id'         => $gameId,
                        'account_type_id' => $accountTypeId,
                        'region_id'       => $regionId,
                        'section_id'      => $sectionId,
                    ]);
                }
            });

        // ── Step 3: drop old enum / string columns ────────────────────────────
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['game', 'account_type', 'region', 'section_slug', 'section_label', 'section_order']);
        });
    }

    public function down(): void
    {
        // ── Restore old columns ───────────────────────────────────────────────
        Schema::table('products', function (Blueprint $table) {
            $table->enum('game', ['valorant', 'fortnite', 'legends'])->nullable()->after('type');
            $table->enum('account_type', ['verified', 'inactive_exclusive', 'nfa_random', 'nfa_guaranteed', 'nfa_inactive', 'standard'])->nullable()->after('game');
            $table->string('region', 20)->nullable()->after('account_type');
            $table->string('section_slug', 32)->nullable()->after('region')->index();
            $table->string('section_label')->nullable()->after('section_slug');
            $table->unsignedTinyInteger('section_order')->default(1)->after('section_label');
        });

        // ── Reverse-populate from FK tables ───────────────────────────────────
        DB::table('products')
            ->join('games', 'products.game_id', '=', 'games.id')
            ->join('account_types', 'products.account_type_id', '=', 'account_types.id')
            ->leftJoin('regions', 'products.region_id', '=', 'regions.id')
            ->leftJoin('sections', 'products.section_id', '=', 'sections.id')
            ->select(
                'products.id',
                'games.slug as game_slug',
                'account_types.slug as account_type_slug',
                'regions.slug as region_slug',
                'sections.slug as section_slug',
                'sections.label as section_label',
                'sections.sort_order as section_order'
            )
            ->orderBy('products.id')
            ->chunk(200, function ($rows) {
                foreach ($rows as $r) {
                    DB::table('products')->where('id', $r->id)->update([
                        'game'          => $r->game_slug,
                        'account_type'  => $r->account_type_slug,
                        'region'        => $r->region_slug,
                        'section_slug'  => $r->section_slug,
                        'section_label' => $r->section_label,
                        'section_order' => $r->section_order ?? 1,
                    ]);
                }
            });

        // ── Drop FK columns ───────────────────────────────────────────────────
        Schema::table('products', function (Blueprint $table) {
            $table->dropForeign(['game_id']);
            $table->dropForeign(['account_type_id']);
            $table->dropForeign(['region_id']);
            $table->dropForeign(['section_id']);
            $table->dropColumn(['game_id', 'account_type_id', 'region_id', 'section_id']);
        });
    }
};
