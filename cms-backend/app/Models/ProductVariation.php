<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductVariation extends Model {
    use HasFactory;
    protected $fillable = ['product_id', 'sku', 'price', 'compare_at_price', 'stock_qty'];

    public function product() {
        return $this->belongsTo(Product::class);
    }
}