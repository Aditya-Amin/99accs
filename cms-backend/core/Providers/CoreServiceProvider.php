<?php
namespace Core\Providers;

use Illuminate\Support\ServiceProvider;
use Core\Foundation\HookManager;
use Core\Foundation\ModuleManager;
use Core\Foundation\PaymentGatewayRegistry;
use Core\Foundation\PluginManager;

class CoreServiceProvider extends ServiceProvider {
    public function register(): void {
        $this->app->singleton('hook.manager', function () {
            return new HookManager();
        });
        $this->app->singleton('payment.manager', function () {
            return new PaymentGatewayRegistry();
        });
        $this->app->singleton(ModuleManager::class);
        $this->app->singleton(PluginManager::class);
    }

    public function boot(): void {
        $this->app->make(ModuleManager::class)->bootModules();
        $this->app->make(PluginManager::class)->bootPlugins();
    }
}
