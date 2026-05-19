<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ── games ─────────────────────────────────────────────────────────────
        Schema::create('games', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('icon')->nullable();
            $table->unsignedTinyInteger('sort_order')->default(0);
            $table->timestamps();
        });

        // ── account_types ─────────────────────────────────────────────────────
        Schema::create('account_types', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->foreignId('game_id')->nullable()->constrained('games')->nullOnDelete();
            $table->string('detail_layout', 32)->default('simple_two');
            $table->unsignedTinyInteger('sort_order')->default(0);
            $table->timestamps();
        });

        // ── regions ───────────────────────────────────────────────────────────
        Schema::create('regions', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->unsignedTinyInteger('sort_order')->default(0);
            $table->timestamps();
        });

        // ── sections ──────────────────────────────────────────────────────────
        Schema::create('sections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('game_id')->constrained('games')->cascadeOnDelete();
            $table->string('slug', 64);
            $table->string('label');
            $table->unsignedTinyInteger('sort_order')->default(1);
            $table->timestamps();
            $table->unique(['game_id', 'slug']);
        });

        // ── Seed games ────────────────────────────────────────────────────────
        $now = now()->toDateTimeString();

        DB::table('games')->insert([
            ['name' => 'Valorant',          'slug' => 'valorant', 'icon' => '/img/icons/header_cat01.svg', 'sort_order' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Fortnite',          'slug' => 'fortnite', 'icon' => '/img/icons/header_cat02.svg', 'sort_order' => 2, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'League of Legends', 'slug' => 'legends',  'icon' => '/img/icons/header_cat03.svg', 'sort_order' => 3, 'created_at' => $now, 'updated_at' => $now],
        ]);

        $valorantId  = DB::table('games')->where('slug', 'valorant')->value('id');
        $fortniteId  = DB::table('games')->where('slug', 'fortnite')->value('id');
        $legendsId   = DB::table('games')->where('slug', 'legends')->value('id');

        // ── Seed account_types ────────────────────────────────────────────────
        DB::table('account_types')->insert([
            ['name' => 'Verified',               'slug' => 'verified',           'game_id' => $valorantId, 'detail_layout' => 'simple_two',    'sort_order' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Inactive / Exclusive',   'slug' => 'inactive_exclusive', 'game_id' => $valorantId, 'detail_layout' => 'rich',          'sort_order' => 2, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'NFA Random Skins',       'slug' => 'nfa_random',         'game_id' => $fortniteId, 'detail_layout' => 'simple_two',    'sort_order' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'NFA Guaranteed Skins',   'slug' => 'nfa_guaranteed',     'game_id' => $fortniteId, 'detail_layout' => 'simple_two',    'sort_order' => 2, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'NFA Inactive Accounts',  'slug' => 'nfa_inactive',       'game_id' => $fortniteId, 'detail_layout' => 'fortnite_four', 'sort_order' => 3, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Standard',               'slug' => 'standard',           'game_id' => $legendsId,  'detail_layout' => 'simple_three',  'sort_order' => 1, 'created_at' => $now, 'updated_at' => $now],
        ]);

        // ── Seed regions ──────────────────────────────────────────────────────
        DB::table('regions')->insert([
            ['name' => 'North America', 'slug' => 'na',    'sort_order' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Europe',        'slug' => 'eu',    'sort_order' => 2, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Asia Pacific',  'slug' => 'apac',  'sort_order' => 3, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Latin America', 'slug' => 'latam', 'sort_order' => 4, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Brazil',        'slug' => 'br',    'sort_order' => 5, 'created_at' => $now, 'updated_at' => $now],
        ]);

        // ── Seed sections ─────────────────────────────────────────────────────
        DB::table('sections')->insert([
            // Valorant
            ['game_id' => $valorantId, 'slug' => 'verified',           'label' => 'VERIFIED',                    'sort_order' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['game_id' => $valorantId, 'slug' => 'inactive_exclusive', 'label' => 'INACTIVE EXCLUSIVE ACCOUNTS', 'sort_order' => 2, 'created_at' => $now, 'updated_at' => $now],
            // Fortnite
            ['game_id' => $fortniteId, 'slug' => 'nfa_random',         'label' => 'NFA Random Skins',            'sort_order' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['game_id' => $fortniteId, 'slug' => 'nfa_guaranteed',     'label' => 'NFA Guaranteed Skins',        'sort_order' => 2, 'created_at' => $now, 'updated_at' => $now],
            ['game_id' => $fortniteId, 'slug' => 'nfa_inactive',       'label' => 'NFA Inactive Accounts',       'sort_order' => 3, 'created_at' => $now, 'updated_at' => $now],
            // League of Legends
            ['game_id' => $legendsId,  'slug' => 'euw',                'label' => 'Europe West (EUW)',           'sort_order' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['game_id' => $legendsId,  'slug' => 'tr',                 'label' => 'Turkey (TR)',                 'sort_order' => 2, 'created_at' => $now, 'updated_at' => $now],
            ['game_id' => $legendsId,  'slug' => 'las',                'label' => 'Latin America South (LAS)',   'sort_order' => 3, 'created_at' => $now, 'updated_at' => $now],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('sections');
        Schema::dropIfExists('regions');
        Schema::dropIfExists('account_types');
        Schema::dropIfExists('games');
    }
};
