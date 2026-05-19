<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductDownload extends Model {
    use HasFactory;
    protected $fillable = ['product_id', 'name', 'file_path', 'download_limit'];

    public function product() {
        return $this->belongsTo(Product::class);
    }
}