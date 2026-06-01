<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Data repair for hashes corrupted by the WordPressUserImporter off-by-one
 * (substr($raw, 4) instead of 3), which stripped the leading "$" from every
 * WordPress 6.8 "$wp$2y$..." bcrypt hash. The stored value became
 * "2y$10$..." (59 chars) instead of "$2y$10$..." (60), so Hash::check() throws
 * "This password does not use the Bcrypt algorithm" and login fails/500s.
 *
 * Re-adding the leading "$" deterministically restores the original hash — the
 * salt + digest bytes are intact, only the marker was lost.
 *
 * Idempotent: the WHERE clause matches only the corrupted shape, so this is a
 * no-op once repaired and on fresh imports made with the fixed importer. A
 * valid bcrypt hash always begins with "$", so a bare "2y$..." is never legit.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('customers')
            ->where('password', 'like', '2y$%')
            ->update(['password' => DB::raw("CONCAT('\$', password)")]);
    }

    public function down(): void
    {
        // Intentionally irreversible: stripping the "$" again would re-break the
        // hashes. The forward migration only restores data the importer damaged.
    }
};
