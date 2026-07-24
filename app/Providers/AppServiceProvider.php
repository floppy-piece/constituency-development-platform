<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Blade;
use App\Services\Gemma4Service;

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
        // Custom Blade directive: @translate('Hello World') or @gemma('Karibu')
        Blade::directive('translate', function ($expression) {
            return "<?php echo app('" . Gemma4Service::class . "')->translateContent($expression, app()->getLocale()); ?>";
        });
    }
}
