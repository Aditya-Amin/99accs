<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Skin extends Model
{
    protected $fillable = ['name', 'slug', 'description', 'image', 'game_id', 'sort_order'];

    public function game(): BelongsTo
    {
        return $this->belongsTo(Game::class);
    }

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'product_skin');
    }
}
