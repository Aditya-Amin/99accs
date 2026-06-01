<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('footer_widgets', function (Blueprint $table) {
            $table->id();
            $table->enum('type', ['menu', 'help_cta'])->default('menu');
            $table->string('col_class', 64)->default('col-lg-3 col-6');
            $table->json('config');
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('footer_widgets');
    }
};
