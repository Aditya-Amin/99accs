<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HomeSection extends Model
{
    protected $fillable = ['key', 'payload'];

    protected $casts = ['payload' => 'array'];

    public static function getSection(string $key): array
    {
        return static::where('key', $key)->first()?->payload ?? [];
    }

    public static function setSection(string $key, array $data): void
    {
        static::updateOrCreate(['key' => $key], ['payload' => $data]);
    }
}
