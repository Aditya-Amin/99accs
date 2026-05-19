<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Expand the type enum to include grouped + digital (avoids doctrine/dbal dependency)
        DB::statement("ALTER TABLE products MODIFY COLUMN `type` ENUM('simple','variable','grouped','digital') NOT NULL DEFAULT 'simple'");

        Schema::table('products', function (Blueprint $table) {
            $table->enum('game', ['valorant', 'fortnite', 'legends'])->nullable()->after('type');
            $table->enum('account_type', ['verified', 'verified_guaranteed', 'exclusive', 'inactive_exclusive'])->nullable()->after('game');
            $table->string('country_code', 10)->nullable()->after('account_type');
            $table->string('country_flag')->nullable()->after('country_code');
            $table->string('region', 20)->nullable()->after('country_flag');
            $table->string('rank')->nullable()->after('region');
            $table->json('agents')->nullable()->after('rank');
            $table->json('skins')->nullable()->after('agents');
            $table->json('buddies')->nullable()->after('skins');
            $table->json('specs')->nullable()->after('buddies');
            $table->json('feature_badges')->nullable()->after('specs');
            $table->unsignedTinyInteger('discount_percent')->nullable()->after('feature_badges');
            $table->string('badge_icon')->nullable()->after('discount_percent');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn([
                'game', 'account_type', 'country_code', 'country_flag',
                'region', 'rank', 'agents', 'skins', 'buddies',
                'specs', 'feature_badges', 'discount_percent', 'badge_icon',
            ]);
        });

        DB::statement("ALTER TABLE products MODIFY COLUMN `type` ENUM('simple','variable') NOT NULL DEFAULT 'simple'");
    }
};
