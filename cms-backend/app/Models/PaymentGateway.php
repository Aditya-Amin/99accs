<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Payment gateway configuration row.
 *
 * Credentials live in an encrypted JSON blob (`credentials`) so secrets are
 * unreadable at rest without APP_KEY. Per-gateway non-secret settings live in
 * `config`. The shape of both columns is gateway-specific:
 *
 *   stripe  → credentials: {secret_key, publishable_key, webhook_secret}
 *             config:      {}
 *
 *   crypto  → credentials: {merchant_id, payment_key}
 *             config:      {callback_url}
 */
class PaymentGateway extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'logo',
        'credentials',
        'config',
        'is_active',
        'is_test_mode',
        'sort_order',
    ];

    protected $casts = [
        'credentials'  => 'encrypted:array',
        'config'       => 'array',
        'is_active'    => 'boolean',
        'is_test_mode' => 'boolean',
        'sort_order'   => 'integer',
    ];

    protected $hidden = [
        'credentials',
    ];

    public function credential(string $key, mixed $default = null): mixed
    {
        return ($this->credentials ?? [])[$key] ?? $default;
    }

    public function setting(string $key, mixed $default = null): mixed
    {
        return ($this->config ?? [])[$key] ?? $default;
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('name');
    }
}
