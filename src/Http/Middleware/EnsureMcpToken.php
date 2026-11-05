<?php

namespace Dkvadratu\DashboardCore\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Laravel\Sanctum\PersonalAccessToken;
use Symfony\Component\HttpFoundation\Response;

class EnsureMcpToken
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        $token = $user?->currentAccessToken();
        abort_unless($request->bearerToken() && $token instanceof PersonalAccessToken, 401);
        abort_unless($user->is_mcp_service_account && ! $user->is_admin && $token->can('mcp'), 403);

        return $next($request);
    }
}
