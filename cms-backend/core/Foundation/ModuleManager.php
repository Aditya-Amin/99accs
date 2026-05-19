<?php
namespace Core\Foundation;

use Illuminate\Support\Facades\File;

class ModuleManager {
    public function bootModules(): void {
        $modulesPath = base_path('core/Modules');
        if (!File::exists($modulesPath)) return;

        $modules = File::directories($modulesPath);
        foreach ($modules as $module) {
            $providerClass = 'Core\\Modules\\' . basename($module) . '\\Providers\\' . basename($module) . 'ServiceProvider';
            if (class_exists($providerClass)) {
                app()->register($providerClass);
            }
        }
    }
}
