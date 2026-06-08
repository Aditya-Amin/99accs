<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('support_tickets', function (Blueprint $table) {
            $table->id();
            // Human-readable id shown on the table & thread header (e.g. "#15941").
            // Distinct from the numeric primary key; generated on create.
            $table->string('ticket_number')->unique();
            $table->foreignId('customer_id')->constrained('customers')->cascadeOnDelete();
            // Optional free-form order reference (e.g. "#AAAA12"). Kept as a display
            // string to match the frontend contract; a real Order relation can be
            // layered on later without touching the API shape.
            $table->string('order_number')->nullable();
            // Product context — drives the logo column on the table. Stored as the
            // game slug ('valorant'|'fortnite'|'legends') to mirror the contract.
            $table->string('game');
            $table->string('subject');
            $table->enum('status', ['new', 'open', 'closed'])->default('new');
            // Timestamp of the most recent reply (excludes the opening message).
            $table->timestamp('last_reply_at')->nullable();
            $table->timestamps();

            $table->index(['customer_id', 'status']);
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('support_tickets');
    }
};
