<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Adds legacy_id (= WooCommerce product/variation ID) to order_items so the
// product lookup can run AFTER products are imported via this SQL:
//
//   UPDATE order_items oi
//   JOIN products p ON p.legacy_id = oi.legacy_id
//   SET oi.product_id = p.id, oi.vendor_id = p.vendor_id
//   WHERE oi.product_id IS NULL;
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            $table->string('legacy_id')->nullable()->index()->after('vendor_id');
        });
    }

    public function down(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            $table->dropIndex(['legacy_id']);
            $table->dropColumn('legacy_id');
        });
    }
};
