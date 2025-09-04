<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;

class ForceHttpsUrls
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
        // Force HTTPS for all URL generation if behind a proxy with HTTPS
        if ($request->header('X-Forwarded-Proto') === 'https' || 
            $request->header('X-Forwarded-Ssl') === 'on' ||
            app()->environment('production')) {
            URL::forceScheme('https');
        }

        return $next($request);
    }
}
