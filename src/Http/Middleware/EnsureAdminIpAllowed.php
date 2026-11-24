<?php

namespace Dkvadratu\DashboardCore\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\IpUtils;
use Symfony\Component\HttpFoundation\Response;

class EnsureAdminIpAllowed
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $allowedIps = config('dashboard.allowed_ips', []);
        $clientIp = $request->ip();

        abort_unless(
            in_array('*', $allowedIps, true)
            || (
                is_string($clientIp)
                && $allowedIps !== []
                && IpUtils::checkIp($clientIp, $allowedIps)
            ),
            response('Not Found', 404, [
                'Content-Type' => 'text/plain; charset=UTF-8',
                'Cache-Control' => 'no-store',
            ]),
        );

        return $next($request);
    }
}
