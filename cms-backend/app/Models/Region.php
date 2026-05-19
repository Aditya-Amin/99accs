<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Region extends Model
{
    protected $fillable = ['name', 'slug', 'code', 'flag', 'class_modifier', 'sort_order'];

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }
}
