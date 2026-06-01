<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Change description_sections from JSON array to plain HTML rich text.
        // Existing JSON data is intentionally cleared — editors will re-enter via RichEditor.
        DB::statement('ALTER TABLE products MODIFY COLUMN description_sections LONGTEXT NULL');

        Schema::table('products', function (Blueprint $table) {
            $table->json('faq_items')->nullable()->after('description_sections');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('faq_items');
        });

        DB::statement('ALTER TABLE products MODIFY COLUMN description_sections JSON NULL');
    }
};
