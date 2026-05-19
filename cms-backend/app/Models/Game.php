<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Game extends Model
{
    protected $fillable = ['name', 'slug', 'icon', 'sort_order'];

    public function accountTypes(): HasMany
    {
        return $this->hasMany(AccountType::class);
    }

    public function sections(): HasMany
    {
        return $this->hasMany(Section::class);
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }
}
