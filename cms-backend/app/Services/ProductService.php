<?php

namespace App\Services;

use App\Models\Product;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ProductService
{
    public function createProduct(array $data): Product
    {
        return DB::transaction(function () use ($data) {
            return Product::create([
                'name'        => $data['name'],
                'slug'        => Str::slug($data['name'] . '-' . uniqid()),
                'sku'         => $data['sku'] ?? null,
                'description' => $data['description'] ?? null,
                'price'       => $data['price'] ?? 0,
                'stock_qty'   => $data['stock_qty'] ?? 0,
                'is_visible'  => $data['is_visible'] ?? true,
            ]);
        });
    }
}
