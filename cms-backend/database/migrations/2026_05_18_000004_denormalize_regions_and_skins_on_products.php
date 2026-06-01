<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ── 1. Many-to-many pivot for regions ─────────────────────────────────
        Schema::dropIfExists('product_region');
        Schema::create('product_region', function (Blueprint $table) {
            $table->unsignedBigInteger('product_id');
            $table->unsignedBigInteger('region_id');

            $table->primary(['product_id', 'region_id']);
            $table->index('region_id', 'idx_pr_region');

            $table->foreign('product_id')->references('id')->on('products')->cascadeOnDelete();
            $table->foreign('region_id')->references('id')->on('regions')->cascadeOnDelete();
        });

        // ── 2. Denormalized JSON columns on products (skip if already present) ─
        Schema::table('products', function (Blueprint $table) {
            if (!Schema::hasColumn('products', 'region_ids')) {
                $table->json('region_ids')->nullable()->after('section_id');
            }
            if (!Schema::hasColumn('products', 'skin_ids')) {
                $table->json('skin_ids')->nullable()->after('region_ids');
            }
        });

        // ── 3 & 4. Backfill pivot + region_ids from old region_id FK ──────────
        if (Schema::hasColumn('products', 'region_id')) {
            $rows = DB::table('products')->whereNotNull('region_id')->get(['id', 'region_id']);
            foreach ($rows as $p) {
                DB::table('product_region')->insertOrIgnore([
                    'product_id' => $p->id,
                    'region_id'  => $p->region_id,
                ]);
            }

            DB::table('products')->whereNotNull('region_id')->get(['id'])->each(function ($p) {
                $ids = DB::table('product_region')
                    ->where('product_id', $p->id)
                    ->pluck('region_id')
                    ->all();

                DB::table('products')
                    ->where('id', $p->id)
                    ->update(['region_ids' => json_encode(array_values($ids))]);
            });
        }

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

        // ── 6. Drop old single-value region FK (only if column still exists) ──
        if (Schema::hasColumn('products', 'region_id')) {
            Schema::table('products', function (Blueprint $table) {
                try {
                    $table->dropForeign(['region_id']);
                } catch (\Throwable $e) {
                    // FK may already be gone
                }
                $table->dropColumn('region_id');
            });
        }
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
