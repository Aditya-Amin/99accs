<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Policy: every account imported from WordPress (is_legacy = true) must set a
 * fresh password before it can log in — a migrated user should never be able to
 * sign in with their old, untrusted password. The login endpoint already
 * returns LEGACY_PASSWORD_RESET_REQUIRED whenever must_reset_password is true;
 * this backfills that flag for the ~15k accounts the earlier import left as
 * false (WordPress 6.8 bcrypt users who could previously log in directly).
 *
 * Customers who have ALREADY reset (markPasswordReset sets is_legacy = false)
 * are excluded by the WHERE clause, so they are not re-flagged.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('customers')
            ->where('is_legacy', true)
            ->where('must_reset_password', false)
            ->update(['must_reset_password' => true]);
    }

    public function down(): void
    {
        // Intentionally irreversible: we can't know which rows were originally
        // false, and reverting would re-open direct login for migrated accounts.
    }
};
