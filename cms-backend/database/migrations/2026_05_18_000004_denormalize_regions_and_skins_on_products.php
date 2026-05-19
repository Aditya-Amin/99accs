<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ── 1. Many-to-many pivot for regions ─────────────────────────────────
        Schema::create('product_region', function (Blueprint $table) {
            $table->unsignedBigInteger('product_id');
            $table->unsignedBigInteger('region_id');

            $table->primary(['product_id', 'region_id']);
            $table->index('region_id', 'idx_pr_region');

            $table->foreign('product_id')->references('id')->on('products')->cascadeOnDelete();
            $table->foreign('region_id')->references('id')->on('regions')->cascadeOnDelete();
        });

        // ── 2. Denormalized JSON columns on products ───────────────────────────
        Schema::table('products', function (Blueprint $table) {
            // JSON arrays of IDs — written on every pivot change, read on every list query
            $table->json('region_ids')->nullable()->after('section_id');
            $table->json('skin_ids')->nullable()->after('region_ids');
        });

        // ── 3. Backfill product_region pivot from existing region_id FK ────────
        $rows = DB::table('products')->whereNotNull('region_id')->get(['id', 'region_id']);
        foreach ($rows as $p) {
            DB::table('product_region')->insertOrIgnore([
                'product_id' => $p->id,
                'region_id'  => $p->region_id,
            ]);
        }

        // ── 4. Backfill products.region_ids from pivot ────────────────────────
        DB::table('products')->whereNotNull('region_id')->get(['id'])->each(function ($p) {
            $ids = DB::table('product_region')
                ->where('product_id', $p->id)
                ->pluck('region_id')
                ->all();

            DB::table('products')
                ->where('id', $p->id)
                ->update(['region_ids' => json_encode(array_values($ids))]);
        });

        // ── 5. Backfill products.skin_ids from product_skin pivot ─────────────
        DB::table('product_skin')->select('product_id')
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

        // ── 6. Drop old single-value region FK ────────────────────────────────
        Schema::table('products', function (Blueprint $table) {
            $table->dropForeign(['region_id']);
            $table->dropColumn('region_id');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['region_ids', 'skin_ids']);
        });

        // Restore single region_id FK (takes first pivot row per product)
        Schema::table('products', function (Blueprint $table) {
            $table->unsignedBigInteger('region_id')->nullable()->after('section_id');
            $table->foreign('region_id')->references('id')->on('regions')->nullOnDelete();
        });

        DB::table('product_region')->select('product_id', DB::raw('MIN(region_id) as region_id'))
            ->groupBy('product_id')
            ->get()
            ->each(fn ($r) => DB::table('products')->where('id', $r->product_id)
                ->update(['region_id' => $r->region_id]));

        Schema::dropIfExists('product_region');
    }
};
