<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Skin taxonomy terms (e.g. "Prime Vandal", "Reaver Karambit", "Gold Midas")
        Schema::create('skins', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->string('image')->nullable();          // optional preview image
            $table->foreignId('game_id')                  // which game this skin belongs to
                  ->nullable()
                  ->constrained('games')
                  ->nullOnDelete();
            $table->unsignedTinyInteger('sort_order')->default(0);
            $table->timestamps();
        });

        // Many-to-many: a product can have many skin tags, a skin can tag many products
        Schema::create('product_skin', function (Blueprint $table) {
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('skin_id')->constrained('skins')->cascadeOnDelete();
            $table->primary(['product_id', 'skin_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_skin');
        Schema::dropIfExists('skins');
    }
};
