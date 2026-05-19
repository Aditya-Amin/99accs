<?php

use App\Models\Category;

try {
    $category = Category::create([
        'name' => 'Test Category',
        'slug' => 'test-category',
        'is_active' => true,
    ]);
    echo "Category created: " . $category->id . "\n";
} catch (\Exception $e) {
    echo "Error creating category: " . $e->getMessage() . "\n";
}
