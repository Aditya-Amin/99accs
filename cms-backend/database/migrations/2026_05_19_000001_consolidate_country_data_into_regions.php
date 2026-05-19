<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ── 1. Add card-display columns to regions ────────────────────────────
        Schema::table('regions', function (Blueprint $table) {
            $table->string('code', 10)->nullable()->after('slug');
            $table->string('flag')->nullable()->after('code');
            $table->string('class_modifier', 50)->nullable()->after('flag');
        });

        // Backfill code for seeded regions (uppercase slug) so each existing
        // region has a card-badge code without admin re-entry.
        DB::table('regions')->whereNull('code')->orderBy('id')->get(['id', 'slug'])
            ->each(function ($r) {
                DB::table('regions')
                    ->where('id', $r->id)
                    ->update(['code' => strtoupper($r->slug)]);
            });

        // ── 2. Restore single region_id FK on products ────────────────────────
        Schema::table('products', function (Blueprint $table) {
            $table->unsignedBigInteger('region_id')->nullable()->after('section_id');
            $table->foreign('region_id')->references('id')->on('regions')->nullOnDelete();
        });

        // ── 3. Backfill products.region_id from country_code ─────────────────
        // For each distinct country_code value, find or create a Region row
        // (copying country_flag / class_modifier from the first product that
        // has them), then point every product at the matching region.
        $codeRows = DB::table('products')
            ->whereNotNull('country_code')
            ->where('country_code', '!=', '')
            ->select('country_code', 'country_flag', 'country_class_modifier')
            ->orderByDesc(DB::raw('country_flag IS NOT NULL'))
            ->get();

        $regionIdByCode = [];
        foreach ($codeRows as $row) {
            $code = strtoupper(trim($row->country_code));
            if (isset($regionIdByCode[$code])) continue;

            $existing = DB::table('regions')->where('code', $code)->first();
            if ($existing) {
                $updates = [];
                if (! $existing->flag && $row->country_flag) {
                    $updates['flag'] = $row->country_flag;
                }
                if (! $existing->class_modifier && $row->country_class_modifier) {
                    $updates['class_modifier'] = $row->country_class_modifier;
                }
                if ($updates) {
                    DB::table('regions')->where('id', $existing->id)->update($updates);
                }
                $regionIdByCode[$code] = $existing->id;
            } else {
                $slug = strtolower($code);
                $i = 1;
                while (DB::table('regions')->where('slug', $slug)->exists()) {
                    $slug = strtolower($code) . '-' . (++$i);
                }
                $regionIdByCode[$code] = DB::table('regions')->insertGetId([
                    'name'           => $code,
                    'slug'           => $slug,
                    'code'           => $code,
                    'flag'           => $row->country_flag,
                    'class_modifier' => $row->country_class_modifier,
                    'sort_order'     => 100,
                    'created_at'     => now(),
                    'updated_at'     => now(),
                ]);
            }
        }

        // Apply region_id to products. Use the country_code on each product
        // to look up its region — no JOIN, just an in-memory map.
        DB::table('products')
            ->whereNotNull('country_code')
            ->where('country_code', '!=', '')
            ->orderBy('id')
            ->chunkById(200, function ($products) use ($regionIdByCode) {
                foreach ($products as $p) {
                    $code = strtoupper(trim($p->country_code));
                    if (isset($regionIdByCode[$code])) {
                        DB::table('products')
                            ->where('id', $p->id)
                            ->update(['region_id' => $regionIdByCode[$code]]);
                    }
                }
            });

        // ── 4. Drop M:N pivot and denormalized region_ids column ─────────────
        // (skin_ids and product_skin stay — only regions are being collapsed)
        try {
            DB::statement('ALTER TABLE products DROP INDEX idx_prod_region_ids');
        } catch (\Throwable $e) {
            // index may not exist on older DBs — ignore
        }

        Schema::dropIfExists('product_region');

        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['region_ids', 'country_code', 'country_flag', 'country_class_modifier']);
        });
    }

    public function down(): void
    {
        // Recreate dropped columns
        Schema::table('products', function (Blueprint $table) {
            $table->string('country_code', 10)->nullable()->after('section_id');
            $table->string('country_flag')->nullable()->after('country_code');
            $table->string('country_class_modifier', 50)->nullable()->after('country_flag');
            $table->json('region_ids')->nullable()->after('section_id');
        });

        // Recreate M:N pivot
        Schema::create('product_region', function (Blueprint $table) {
            $table->unsignedBigInteger('product_id');
            $table->unsignedBigInteger('region_id');
            $table->primary(['product_id', 'region_id']);
            $table->index('region_id', 'idx_pr_region');
            $table->foreign('product_id')->references('id')->on('products')->cascadeOnDelete();
            $table->foreign('region_id')->references('id')->on('regions')->cascadeOnDelete();
        });

        // Reverse-populate country_* on each product from its region
        DB::table('products')
            ->join('regions', 'products.region_id', '=', 'regions.id')
            ->select('products.id', 'regions.code', 'regions.flag', 'regions.class_modifier', 'regions.id as rid')
            ->orderBy('products.id')
            ->chunk(200, function ($rows) {
                foreach ($rows as $r) {
                    DB::table('products')->where('id', $r->id)->update([
                        'country_code'           => $r->code,
                        'country_flag'           => $r->flag,
                        'country_class_modifier' => $r->class_modifier,
                        'region_ids'             => json_encode([$r->rid]),
                    ]);
                    DB::table('product_region')->insertOrIgnore([
                        'product_id' => $r->id,
                        'region_id'  => $r->rid,
                    ]);
                }
            });

        try {
            DB::statement('ALTER TABLE products ADD INDEX idx_prod_region_ids ((CAST(region_ids AS UNSIGNED ARRAY)))');
        } catch (\Throwable $e) { /* ignore */ }

        Schema::table('products', function (Blueprint $table) {
            $table->dropForeign(['region_id']);
            $table->dropColumn('region_id');
        });

        Schema::table('regions', function (Blueprint $table) {
            $table->dropColumn(['code', 'flag', 'class_modifier']);
        });
    }
};
