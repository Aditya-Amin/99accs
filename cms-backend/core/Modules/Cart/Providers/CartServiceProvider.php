<?php
namespace Core\Modules\Cart\Providers;

use Illuminate\Support\ServiceProvider;

class CartServiceProvider extends ServiceProvider {
    public function register(): void {
        // Register Cart Bindings
    }

    public function boot(): void {
        $this->loadMigrationsFrom(__DIR__.'/../Database/Migrations');
    }
}
