<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{
    use HasFactory;

    public const STATUS_PENDING    = 'pending';
    public const STATUS_PROCESSING = 'processing';
    public const STATUS_COMPLETED  = 'completed';
    public const STATUS_CANCELLED  = 'cancelled';

    protected $fillable = [
        'customer_id',
        'number',
        'checkout_token',
        'status',
        'total_price',
        'payment_status',
        'payment_method',
        'payment_provider_id',
        'client_secret',
        'payment_url',
        'expires_at',
        'paid_at',
        // financial extras from existing migration
        'shipping_cost',
        'vat_tax',
        'discount_amount',
        'coupon_code',
    ];

    protected $casts = [
        'total_price'     => 'decimal:2',
        'shipping_cost'   => 'decimal:2',
        'vat_tax'         => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'expires_at'      => 'datetime',
        'paid_at'         => 'datetime',
    ];

    protected $hidden = ['client_secret'];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function isPaid(): bool
    {
        return $this->payment_status === 'paid';
    }

    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }
}
