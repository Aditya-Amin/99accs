<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->uuid('checkout_token')->unique()->after('id');
            $table->string('payment_provider_id')->nullable()->after('payment_method');
            $table->text('client_secret')->nullable()->after('payment_provider_id');
            $table->string('payment_url')->nullable()->after('client_secret');
            $table->timestamp('expires_at')->nullable()->after('payment_url');
            $table->timestamp('paid_at')->nullable()->after('expires_at');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn([
                'checkout_token', 'payment_provider_id',
                'client_secret', 'payment_url', 'expires_at', 'paid_at',
            ]);
        });
    }
};
