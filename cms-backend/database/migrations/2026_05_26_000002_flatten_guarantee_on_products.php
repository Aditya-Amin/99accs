<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->string('guarantee_title')->nullable()->after('description_sections');
            $table->longText('guarantee_body')->nullable()->after('guarantee_title');
            $table->dropColumn('guarantee');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['guarantee_title', 'guarantee_body']);
            $table->json('guarantee')->nullable();
        });
    }
};
