<?php

namespace Dkvadratu\DashboardCore\Http\Middleware;

use Closure;
use Filament\Facades\Filament;
use Filament\Models\Contracts\FilamentUser;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureDashboardAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = Filament::auth()->user();
        abort_unless($user instanceof FilamentUser && $user->canAccessPanel(Filament::getCurrentPanel()), 403);

        return $next($request);
    }
}
