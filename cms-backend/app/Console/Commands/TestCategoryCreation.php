<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class TestCategoryCreation extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:test-category-creation';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test creating a category via Eloquent';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Testing Category Creation...');
        
        try {
            $category = \App\Models\Category::create([
                'name' => 'Category Manual Test',
                'slug' => 'category-manual-test-' . time(),
                'is_active' => true,
            ]);
            $this->info("Category created successfully! ID: {$category->id}");
        } catch (\Exception $e) {
            $this->error("Failed: " . $e->getMessage());
        }
    }
}
