<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\URL;
use Illuminate\Pagination\Paginator;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Force HTTPS detection as early as possible
        if (app()->environment('production') || env('FORCE_HTTPS', false)) {
            $this->forceHttpsScheme();
            $this->configurePaginationForHttps();
        }
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Force HTTPS in production environment or when explicitly configured
        if (app()->environment('production') || env('FORCE_HTTPS', false)) {
            $this->forceHttpsScheme();
            $this->configurePaginationForHttps();
        }
    }

    /**
     * Force HTTPS scheme for URL generation
     */
    private function forceHttpsScheme(): void
    {
        URL::forceScheme('https');
        URL::forceRootUrl(str_replace('http://', 'https://', env('APP_URL')));
        
        // Also check proxy headers
        if (request() && (
            request()->header('X-Forwarded-Proto') === 'https' ||
            request()->header('X-Forwarded-Ssl') === 'on' ||
            request()->server('HTTP_X_FORWARDED_PROTO') === 'https' ||
            request()->server('HTTP_X_FORWARDED_SSL') === 'on'
        )) {
            URL::forceScheme('https');
        }
    }

    /**
     * Configure pagination to always use HTTPS
     */
    private function configurePaginationForHttps(): void
    {
        // Set default pagination views
        Paginator::defaultView('pagination::bootstrap-4');
        Paginator::defaultSimpleView('pagination::simple-bootstrap-4');
        
        // Force HTTPS for all pagination URLs
        Paginator::currentPathResolver(function () {
            $url = request() ? request()->url() : env('APP_URL');
            return str_replace('http://', 'https://', $url);
        });
        
        // Override URL resolver for pagination links
        Paginator::currentPageResolver(function ($pageName = 'page') {
            URL::forceScheme('https');
            return request() ? request()->get($pageName, 1) : 1;
        });
    }
}
