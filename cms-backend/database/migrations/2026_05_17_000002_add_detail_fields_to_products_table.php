<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Fix account_type enum — extend with Fortnite + Legends variants
        DB::statement("ALTER TABLE products MODIFY COLUMN `account_type`
            ENUM('verified','inactive_exclusive','nfa_random','nfa_guaranteed','nfa_inactive','standard')
            NULL");

        Schema::table('products', function (Blueprint $table) {
            // Section grouping (catalog page buckets)
            $table->string('section_slug', 32)->nullable()->after('account_type')->index();
            $table->string('section_label')->nullable()->after('section_slug');
            $table->unsignedTinyInteger('section_order')->default(1)->after('section_label');

            // Pricing extras
            $table->decimal('price_max', 10, 2)->nullable()->after('price');

            // Country extras
            $table->string('country_class_modifier', 16)->nullable()->after('country_flag');

            // Card behaviour
            $table->boolean('has_gallery')->default(false)->after('country_class_modifier');

            // Sync metadata (game API import)
            $table->string('source_provider', 32)->nullable()->after('badge_icon');
            $table->string('external_id')->nullable()->after('source_provider');
            $table->timestamp('synced_at')->nullable()->after('external_id');

            // Detail-only — heavy JSON, omitted from list queries
            $table->json('agents_detailed')->nullable();
            $table->unsignedSmallInteger('agents_count')->nullable();
            $table->json('profile_info')->nullable();
            $table->json('skin_inventory')->nullable();
            $table->json('skin_filters')->nullable();
            $table->json('buddy_inventory')->nullable();
            $table->json('account_level')->nullable();
            $table->json('account_stats')->nullable();
            $table->json('locker')->nullable();
            $table->json('seasons')->nullable();
            $table->json('description_sections')->nullable();
            $table->unsignedSmallInteger('min_quantity')->nullable();
            $table->string('last_match_label')->nullable();
            $table->json('guarantee')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn([
                'section_slug', 'section_label', 'section_order',
                'price_max', 'country_class_modifier', 'has_gallery',
                'source_provider', 'external_id', 'synced_at',
                'agents_detailed', 'agents_count', 'profile_info',
                'skin_inventory', 'skin_filters', 'buddy_inventory',
                'account_level', 'account_stats', 'locker', 'seasons',
                'description_sections', 'min_quantity', 'last_match_label', 'guarantee',
            ]);
        });

        DB::statement("ALTER TABLE products MODIFY COLUMN `account_type`
            ENUM('verified','verified_guaranteed','exclusive','inactive_exclusive')
            NULL");
    }
};
