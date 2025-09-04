<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Illuminate\Pagination\Paginator;

class ForcePaginationHttps
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        // Force HTTPS for all URL generation
        if (app()->environment('production') || env('FORCE_HTTPS', false)) {
            URL::forceScheme('https');
            URL::forceRootUrl(str_replace('http://', 'https://', env('APP_URL')));
            
            // Override pagination resolvers
            Paginator::currentPathResolver(function () use ($request) {
                return str_replace('http://', 'https://', $request->url());
            });
            
            Paginator::currentPageResolver(function ($pageName = 'page') use ($request) {
                URL::forceScheme('https');
                return $request->get($pageName, 1);
            });
        }

        $response = $next($request);

        // If it's an Inertia response with pagination data, fix the URLs
        if ($response instanceof \Illuminate\Http\Response && 
            $request->header('X-Inertia') && 
            (app()->environment('production') || env('FORCE_HTTPS', false))) {
            
            $content = $response->getContent();
            // Replace any http:// URLs with https:// in the response
            $content = str_replace('http://lagoaged.lagoasanta.mg.gov.br', 'https://lagoaged.lagoasanta.mg.gov.br', $content);
            $response->setContent($content);
        }

        return $response;
    }
}
