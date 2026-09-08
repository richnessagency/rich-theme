<?php

declare(strict_types=1);

namespace Richness\RichTheme;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Richness\RichTheme\Console\ConvertHtmlThemeCommand;

final class ThemeServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/theme.php', 'theme');

        $this->app->singleton(ThemeManager::class);
        $this->app->alias(ThemeManager::class, 'theme');

        $this->app->bind(ThemeContext::class, static fn ($app) => $app->make(ThemeManager::class)->resolve()
        );
    }

    public function boot(): void
    {
        // Publish config
        $this->publishes([
            __DIR__.'/../config/theme.php' => config_path('theme.php'),
        ], 'rich-theme-config');

        // Register package views (admin theme pages & components)
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'rich-theme');

        // Register Blade component alias: <x-theme-styles />
        Blade::component('rich-theme::components.theme-styles', 'theme-styles');

        // Register admin routes
        if (file_exists(__DIR__.'/../routes/admin.php')) {
            $this->loadRoutesFrom(__DIR__.'/../routes/admin.php');
        }

        if (file_exists(__DIR__.'/../routes/web.php')) {
            $this->loadRoutesFrom(__DIR__.'/../routes/web.php');
        }

        if ($this->app->runningInConsole()) {
            $this->commands([
                ConvertHtmlThemeCommand::class,
            ]);
        }

        // Register theme view overrides (WordPress-style)
        try {
            $context = $this->app->make(ThemeContext::class);
            $this->app->make(ThemeManager::class)->registerViewOverrides($context);
            View::share('themeContext', $context);
        } catch (\Throwable $e) {
            // Graceful fallback during artisan/migrations/composer install
            report($e);
        }
    }
}
