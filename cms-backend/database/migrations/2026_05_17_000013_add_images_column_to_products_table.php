<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            // Stores image URL array for seeded/API-imported products.
            // Spatie MediaLibrary is used when images are admin-uploaded.
            // ProductListResource falls back to this column when no Spatie media exists.
            $table->json('images')->nullable()->after('has_gallery');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('images');
        });
    }
};
