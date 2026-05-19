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
        Schema::table('site_settings', function (Blueprint $table) {
            // Stripe
            $table->boolean('stripe_enabled')->default(false);
            $table->string('stripe_key')->nullable();
            $table->string('stripe_secret')->nullable();

            // PayPal
            $table->boolean('paypal_enabled')->default(false);
            $table->string('paypal_client_id')->nullable();
            $table->string('paypal_secret')->nullable();

            // Razorpay
            $table->boolean('razorpay_enabled')->default(false);
            $table->string('razorpay_key')->nullable();
            $table->string('razorpay_secret')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('site_settings', function (Blueprint $table) {
            $table->dropColumn([
                'stripe_enabled',
                'stripe_key',
                'stripe_secret',
                'paypal_enabled',
                'paypal_client_id',
                'paypal_secret',
                'razorpay_enabled',
                'razorpay_key',
                'razorpay_secret',
            ]);
        });
    }
};
