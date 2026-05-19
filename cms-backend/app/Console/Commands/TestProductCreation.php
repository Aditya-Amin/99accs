<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class TestProductCreation extends Command
{
    protected $signature = 'app:test-product-creation';
    protected $description = 'Test creating a product via Eloquent';

    public function handle()
    {
        $this->info('Testing Product Creation...');
        
        try {
            // Create a brand first
            $brand = \App\Models\Brand::create([
                'name' => 'Test Brand',
                'slug' => 'test-brand-' . time(),
            ]);
            $this->info("Brand created: {$brand->id}");

            // Create a category
            $category = \App\Models\Category::create([
                'name' => 'Test Category',
                'slug' => 'test-cat-' . time(),
            ]);
            $this->info("Category created: {$category->id}");

            // Create Product
            $product = \App\Models\Product::create([
                'brand_id' => $brand->id,
                'name' => 'Test Product',
                'slug' => 'test-product-' . time(),
                'sku' => 'SKU-' . time(),
                'description' => 'Test Description',
                'price' => 100.00,
                'stock_qty' => 10,
                'type' => 'simple',
            ]);
            $this->info("Product created successfully! ID: {$product->id}");

            // Attach Category
            $product->categories()->attach($category->id);
            $this->info("Attached Category to Product.");

        } catch (\Exception $e) {
            $this->error("Failed: " . $e->getMessage());
        }
    }
}
