<?php

namespace App\Models;

use App\Notifications\ResetPasswordNotification;
use Illuminate\Auth\Passwords\CanResetPassword;
use Illuminate\Contracts\Auth\CanResetPassword as CanResetPasswordContract;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class Customer extends Authenticatable implements CanResetPasswordContract
{
    use HasApiTokens;
    use HasFactory;
    use Notifiable;
    use CanResetPassword;

    protected $fillable = [
        'first_name',
        'last_name',
        'email',
        'phone',
        'password',
        'must_reset_password',
        'is_legacy',
        'migrated_at',
        'legacy_id',
        'last_login_at',
        'last_login_ip',
        'is_blocked',
        'email_verified_at',
        'total_spent',
        'legacy_orders_count',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'password'             => 'hashed',
        'must_reset_password'  => 'boolean',
        'is_legacy'            => 'boolean',
        'is_blocked'           => 'boolean',
        'migrated_at'          => 'datetime',
        'last_login_at'        => 'datetime',
        'email_verified_at'    => 'datetime',
        'total_spent'          => 'decimal:2',
        'legacy_orders_count'  => 'integer',
    ];

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function cart()
    {
        return $this->hasOne(Cart::class);
    }

    public function getFullNameAttribute(): string
    {
        return trim("{$this->first_name} {$this->last_name}");
    }

    public function sendPasswordResetNotification($token): void
    {
        $this->notify(new ResetPasswordNotification($token));
    }

    public function markPasswordReset(): void
    {
        $this->forceFill([
            'must_reset_password' => false,
            'is_legacy'           => false,
        ])->save();
    }

    public function trackLogin(?string $ip): void
    {
        $this->forceFill([
            'last_login_at' => now(),
            'last_login_ip' => $ip,
        ])->save();
    }
}
