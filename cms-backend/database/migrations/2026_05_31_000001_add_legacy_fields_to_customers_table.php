<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->decimal('total_spent', 12, 2)->default(0)->after('email_verified_at');
            $table->unsignedInteger('legacy_orders_count')->default(0)->after('total_spent');
        });
    }

    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropColumn(['total_spent', 'legacy_orders_count']);
        });
    }
};
