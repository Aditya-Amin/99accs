<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Fix: my earlier migration used morphs('user'), creating an unwanted user_type column
// that Filament's ImportAction never populates → SQL strict mode rejects the insert.
// Match Filament's official schema: foreignId('user_id') only.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('imports', function (Blueprint $table) {
            // morphs() created index on (user_type, user_id) — drop it first
            $table->dropIndex(['user_type', 'user_id']);
            $table->dropColumn('user_type');
        });

        // Add foreign key constraint that the morphs() shorthand skipped
        Schema::table('imports', function (Blueprint $table) {
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('imports', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->string('user_type')->after('user_id');
            $table->index(['user_type', 'user_id']);
        });
    }
};
