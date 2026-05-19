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
        Schema::table('categories', function (Blueprint $table) {
            $table->dropColumn('image');
        });

        Schema::table('brands', function (Blueprint $table) {
            $table->dropColumn('image');
        });

        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['featured_image', 'images']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->string('image')->nullable()->after('slug');
        });

        Schema::table('brands', function (Blueprint $table) {
            $table->string('image')->nullable()->after('slug');
        });

        Schema::table('products', function (Blueprint $table) {
            $table->string('featured_image')->nullable()->after('sku');
            $table->json('images')->nullable()->after('featured_image');
        });
    }
};
