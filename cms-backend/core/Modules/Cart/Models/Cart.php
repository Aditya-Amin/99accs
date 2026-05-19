<?php

namespace Core\Modules\Cart\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Cart extends Model
{
    use HasFactory;

    public function items() {
        return $this->hasMany(CartItem::class);
    }
}


