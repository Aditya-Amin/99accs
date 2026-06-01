<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// Doctrine DBAL crashes on MySQL functional indexes (e.g. CAST(skin_ids AS UNSIGNED ARRAY)).
// Use raw ALTER TABLE to bypass DBAL schema introspection entirely.
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('products', 'compare_at_price')) {
            DB::statement('ALTER TABLE products RENAME COLUMN compare_at_price TO regular_price');
        }
        if (Schema::hasColumn('products', 'price_max')) {
            DB::statement('ALTER TABLE products DROP COLUMN price_max');
        }
        if (Schema::hasColumn('products', 'discount_percent')) {
            DB::statement('ALTER TABLE products DROP COLUMN discount_percent');
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('products', 'regular_price')) {
            DB::statement('ALTER TABLE products RENAME COLUMN regular_price TO compare_at_price');
        }
        if (!Schema::hasColumn('products', 'price_max')) {
            DB::statement('ALTER TABLE products ADD COLUMN price_max DECIMAL(10,2) NULL');
        }
        if (!Schema::hasColumn('products', 'discount_percent')) {
            DB::statement('ALTER TABLE products ADD COLUMN discount_percent TINYINT UNSIGNED NULL');
        }
    }
};
