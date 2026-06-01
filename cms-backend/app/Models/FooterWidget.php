<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FooterWidget extends Model
{
    protected $fillable = ['type', 'col_class', 'config', 'sort_order', 'is_active'];

    protected $casts = [
        'config'    => 'array',
        'is_active' => 'boolean',
    ];

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('id');
    }
}
