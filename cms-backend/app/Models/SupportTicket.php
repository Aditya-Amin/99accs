<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class SupportTicket extends Model
{
    use HasFactory;

    public const STATUS_NEW    = 'new';
    public const STATUS_OPEN   = 'open';
    public const STATUS_CLOSED = 'closed';

    protected $fillable = [
        'ticket_number',
        'customer_id',
        'order_number',
        'game',
        'subject',
        'status',
        'last_reply_at',
    ];

    protected $casts = [
        'last_reply_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        // Auto-assign a unique human-readable number when one isn't supplied
        // (the API path). The seeder passes explicit numbers, which are kept.
        static::creating(function (SupportTicket $ticket) {
            if (blank($ticket->ticket_number)) {
                $ticket->ticket_number = static::generateTicketNumber();
            }
        });
    }

    public static function generateTicketNumber(): string
    {
        do {
            $candidate = '#' . str_pad((string) random_int(10000, 99999), 5, '0', STR_PAD_LEFT);
        } while (static::where('ticket_number', $candidate)->exists());

        return $candidate;
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function messages(): HasMany
    {
        return $this->hasMany(SupportTicketMessage::class, 'ticket_id');
    }

    /** The original message — the ticket body the customer opened with. */
    public function openingMessage(): HasMany
    {
        return $this->messages()->where('is_opening', true);
    }

    // ─── State transitions ──────────────────────────────────────────────────

    /** Staff has engaged: a brand-new ticket moves to "open" on first reply. */
    public function markRepliedByStaff(): void
    {
        if ($this->status === self::STATUS_NEW) {
            $this->status = self::STATUS_OPEN;
        }
        $this->last_reply_at = now();
        $this->save();
    }

    /** Customer replied: stamp the reply time and re-open a closed thread. */
    public function markRepliedByCustomer(): void
    {
        if ($this->status === self::STATUS_CLOSED) {
            $this->status = self::STATUS_OPEN;
        }
        $this->last_reply_at = now();
        $this->save();
    }

    public function close(): void
    {
        $this->status = self::STATUS_CLOSED;
        $this->save();
    }
}
