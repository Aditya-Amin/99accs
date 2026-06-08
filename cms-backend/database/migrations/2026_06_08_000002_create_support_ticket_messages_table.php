<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('support_ticket_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ticket_id')
                ->constrained('support_tickets')
                ->cascadeOnDelete();
            // Polymorphic author: either a Customer (storefront owner) or a User
            // (admin staff). Nullable so removing an admin account leaves the
            // historical thread intact rather than cascading the message away.
            $table->nullableMorphs('author');
            $table->text('body');
            // True for the very first message (the original ticket body). The UI
            // labels owner-authored opening messages "started the conversation".
            $table->boolean('is_opening')->default(false);
            $table->timestamps();

            $table->index(['ticket_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('support_ticket_messages');
    }
};
