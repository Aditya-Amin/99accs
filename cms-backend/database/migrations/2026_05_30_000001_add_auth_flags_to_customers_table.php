<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->boolean('must_reset_password')->default(false)->after('password');
            $table->boolean('is_legacy')->default(false)->after('must_reset_password');
            $table->timestamp('migrated_at')->nullable()->after('is_legacy');
            $table->string('legacy_id')->nullable()->after('migrated_at');
            $table->timestamp('last_login_at')->nullable()->after('legacy_id');
            $table->string('last_login_ip', 45)->nullable()->after('last_login_at');
            $table->boolean('is_blocked')->default(false)->after('last_login_ip');
            $table->timestamp('email_verified_at')->nullable()->after('is_blocked');

            $table->index('legacy_id');
            $table->index('must_reset_password');
            $table->index('is_blocked');
        });
    }

    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropIndex(['legacy_id']);
            $table->dropIndex(['must_reset_password']);
            $table->dropIndex(['is_blocked']);
            $table->dropColumn([
                'must_reset_password',
                'is_legacy',
                'migrated_at',
                'legacy_id',
                'last_login_at',
                'last_login_ip',
                'is_blocked',
                'email_verified_at',
            ]);
        });
    }
};
