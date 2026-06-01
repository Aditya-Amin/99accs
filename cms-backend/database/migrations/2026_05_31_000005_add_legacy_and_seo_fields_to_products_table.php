<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Adds:
//   legacy_id          — WooCommerce product/variation ID (for order_items lookup)
//   legacy_categories  — WC categories as JSON (read-only reference)
//   meta_title         — Yoast _yoast_wpseo_title (overrides <title>)
//   meta_description   — Yoast _yoast_wpseo_metadesc (Google SERP snippet)
//   meta_keywords      — Yoast _yoast_wpseo_metakeywords (legacy; Bing only)
//   is_cornerstone     — Yoast _yoast_wpseo_is_cornerstone (pillar content flag)
//   canonical_url      — optional override (rare; most products auto-canonical)
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            // Legacy reference
            $table->string('legacy_id')->nullable()->unique()->after('id');
            $table->json('legacy_categories')->nullable()->after('legacy_id');

            // SEO — read by Next.js generateMetadata()
            $table->string('meta_title')->nullable()->after('description');
            $table->string('meta_description', 500)->nullable()->after('meta_title');
            $table->string('meta_keywords', 500)->nullable()->after('meta_description');
            $table->boolean('is_cornerstone')->default(false)->after('meta_keywords');
            $table->string('canonical_url')->nullable()->after('is_cornerstone');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn([
                'legacy_id',
                'legacy_categories',
                'meta_title',
                'meta_description',
                'meta_keywords',
                'is_cornerstone',
                'canonical_url',
            ]);
        });
    }
};
