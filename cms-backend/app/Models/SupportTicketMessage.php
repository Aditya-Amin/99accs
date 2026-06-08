<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class SupportTicketMessage extends Model
{
    use HasFactory;

    protected $fillable = [
        'ticket_id',
        'author_id',
        'author_type',
        'body',
        'is_opening',
    ];

    protected $casts = [
        'is_opening' => 'boolean',
    ];

    public function ticket(): BelongsTo
    {
        return $this->belongsTo(SupportTicket::class, 'ticket_id');
    }

    /** The author is either a Customer (owner) or a User (admin staff). */
    public function author(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * True when written by the ticket's owner (a Customer). The customer-facing
     * API renders these as "You"; staff messages show the admin's name.
     */
    public function isOwnerAuthored(): bool
    {
        return $this->author_type === Customer::class;
    }
}
