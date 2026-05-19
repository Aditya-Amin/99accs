<?php

namespace Core\Modules\Cart\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CartItem extends Model
{
    use HasFactory;

    public function product() {
        return $this->belongsTo(\App\Models\Product::class);
    }
    public function variation() {
        return $this->belongsTo(\App\Models\ProductVariation::class, 'product_variation_id');
    }
}


