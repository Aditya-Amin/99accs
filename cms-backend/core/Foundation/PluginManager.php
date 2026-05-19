<?php
namespace Core\Foundation;

use Illuminate\Support\Facades\File;

class PluginManager {
    public function bootPlugins(): void {
        $pluginsPath = base_path('core/Plugins');
        if (!File::exists($pluginsPath)) return;

        $plugins = File::directories($pluginsPath);
        foreach ($plugins as $plugin) {
            $providerClass = 'Core\\Plugins\\' . basename($plugin) . '\\Providers\\' . basename($plugin) . 'ServiceProvider';
            if (class_exists($providerClass)) {
                app()->register($providerClass);
            }
        }
    }
}
