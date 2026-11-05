<?php

namespace Dkvadratu\DashboardCore\Http\Middleware;

use Closure;
use Dkvadratu\DashboardCore\Models\Redirect;
use Dkvadratu\DashboardCore\Support\RedirectPaths;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;

class RedirectRequests
{
    public function handle(Request $request, Closure $next): Response
    {
        $path = '/'.ltrim($request->decodedPath(), '/');
        if (! config('dashboard-redirects.enabled', false)
            || ! in_array($request->method(), ['GET', 'HEAD'], true)
            || $request->ajax() || $request->expectsJson() || $request->hasHeader('X-Livewire')
            || $request->hasHeader('Authorization')
            || RedirectPaths::isExcluded($path)) {
            return $next($request);
        }

        $redirects = $this->redirects();
        $redirect = $redirects[$path] ?? null;
        if (! $redirect || ! $this->isActive($redirect)) {
            return $next($request);
        }

        $seen = [$path => true];
        $destination = $redirect['destination'];
        while (true) {
            if (isset($seen[$destination]) || ! RedirectPaths::isLocal($destination) || RedirectPaths::isExcluded($destination)) {
                return $next($request);
            }
            $seen[$destination] = true;
            $following = $redirects[$destination] ?? null;
            if (! $following || ! $this->isActive($following)) {
                break;
            }
            $destination = $following['destination'];
        }

        if (config('dashboard-redirects.record_hits', false)) {
            $request->attributes->set('dashboard_redirect_id', $redirect['id']);
        }

        return new RedirectResponse($redirect['destination'], $redirect['status_code']);
    }

    public function terminate(Request $request, Response $response): void
    {
        if ($id = $request->attributes->get('dashboard_redirect_id')) {
            Redirect::query()->whereKey($id)->increment('hits');
        }
    }

    /** @return array<string, array{id: int, destination: string, status_code: int, expires_at: ?int}> */
    private function redirects(): array
    {
        $load = fn (): array => Redirect::query()->where('enabled', true)
            ->get(['id', 'source', 'destination', 'status_code', 'redirect_valid_until'])
            ->mapWithKeys(fn (Redirect $redirect): array => [$redirect->source => [
                'id' => $redirect->id,
                'destination' => $redirect->destination,
                'status_code' => $redirect->status_code,
                'expires_at' => $redirect->redirect_valid_until?->getTimestamp(),
            ]])->all();

        if ((new Redirect)->getConnection()->transactionLevel() > 0) {
            return $load();
        }

        return Cache::remember(Redirect::CACHE_KEY, (int) config('dashboard-redirects.cache_seconds', 300), $load);
    }

    /** @param array{id: int, destination: string, status_code: int, expires_at: ?int} $redirect */
    private function isActive(array $redirect): bool
    {
        return in_array($redirect['status_code'], [301, 302], true)
            && ($redirect['expires_at'] === null || $redirect['expires_at'] > now()->getTimestamp());
    }
}
