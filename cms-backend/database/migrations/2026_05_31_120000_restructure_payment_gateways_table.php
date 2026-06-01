<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payment_gateways', function (Blueprint $table) {
            $table->text('description')->nullable()->after('slug');
            // Encrypted JSON blob of secrets (merchant_id, secret_key, webhook_secret, etc.)
            $table->text('credentials')->nullable()->after('logo');
            // Non-secret per-gateway config (e.g. callback URLs, mode flags)
            $table->json('config')->nullable()->after('credentials');
            $table->boolean('is_test_mode')->default(true)->after('is_active');
            $table->unsignedInteger('sort_order')->default(0)->after('is_test_mode');
        });

        // The flat public_key/secret_key columns are obsolete — credentials JSON replaces them
        Schema::table('payment_gateways', function (Blueprint $table) {
            if (Schema::hasColumn('payment_gateways', 'public_key')) {
                $table->dropColumn('public_key');
            }
            if (Schema::hasColumn('payment_gateways', 'secret_key')) {
                $table->dropColumn('secret_key');
            }
        });
    }

    public function down(): void
    {
        Schema::table('payment_gateways', function (Blueprint $table) {
            $table->string('public_key')->nullable();
            $table->string('secret_key')->nullable();
            $table->dropColumn(['description', 'credentials', 'config', 'is_test_mode', 'sort_order']);
        });
    }
};
