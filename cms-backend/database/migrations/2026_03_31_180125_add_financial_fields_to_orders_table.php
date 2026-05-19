<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->decimal('shipping_cost', 10, 2)->default(0)->after('payment_method');
            $table->decimal('vat_tax', 10, 2)->default(0)->after('shipping_cost');
            $table->decimal('discount_amount', 10, 2)->default(0)->after('vat_tax');
            $table->string('coupon_code')->nullable()->after('discount_amount');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['shipping_cost', 'vat_tax', 'discount_amount', 'coupon_code']);
        });
    }
};
