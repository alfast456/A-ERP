<?php

namespace App\Providers;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Blade Component Aliases for TailAdmin modules if folders exist
        $componentMappings = [
            'views/components/main/dashboard' => 'dashboard',
            'views/components/main/dashboard/finance' => 'finance',
            'views/components/main/dashboard/hr' => 'hr',
            'views/components/main/dashboard/logistics' => 'logistics',
            'views/components/main/dashboard/sales' => 'sales',
            'views/components/main/dashboard/ai' => 'ai-dashboard',
            'views/components/main/ai-assistant' => 'ai',
            'views/components/main/e-commerce' => 'ecommerce',
            'views/components/main/forms' => 'form',
            'views/components/main/profile' => 'profile',
            'views/components/main/tables' => 'tables',
            'views/components/main/task' => 'task',
            'views/components/main/pages/faq' => 'faq',
            'views/components/main/pages/file-manager' => 'file-manager',
            'views/components/main/pages/pricing-tables' => 'pricing',
            'views/components/main/pages/integrations' => 'integration',
        ];

        foreach ($componentMappings as $path => $alias) {
            $fullPath = resource_path($path);
            if (is_dir($fullPath)) {
                Blade::anonymousComponentPath($fullPath, $alias);
            }
        }
    }
}
