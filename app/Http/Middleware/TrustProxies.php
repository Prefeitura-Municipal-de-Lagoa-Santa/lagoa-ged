<?php

namespace App\Http\Middleware;

use Illuminate\Http\Middleware\TrustProxies as Middleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class TrustProxies extends Middleware
{
    /**
     * The trusted proxies for this application.
     * In Docker environment, we trust all proxies (*) as they're typically in the same network
     * You can also specify specific IP ranges like ['10.1.7.0/24'] if needed
     *
     * @var array<int, string>|string|null
     */
    protected $proxies = '*';

    /**
     * The headers that should be used to detect proxies.
     *
     * @var int
     */
    protected $headers =
        Request::HEADER_X_FORWARDED_FOR |
        Request::HEADER_X_FORWARDED_HOST |
        Request::HEADER_X_FORWARDED_PORT |
        Request::HEADER_X_FORWARDED_PROTO |
        Request::HEADER_X_FORWARDED_AWS_ELB;

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, \Closure $next)
    {
        // Debug: Log proxy headers (remove in production)
        if (app()->environment(['local', 'staging'])) {
            Log::info('Proxy Headers Debug', [
                'X-Forwarded-Proto' => $request->header('X-Forwarded-Proto'),
                'X-Forwarded-Ssl' => $request->header('X-Forwarded-Ssl'),
                'isSecure' => $request->isSecure(),
                'scheme' => $request->getScheme(),
            ]);
        }

        return parent::handle($request, $next);
    }
}
