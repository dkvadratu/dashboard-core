<?php

namespace Dkvadratu\DashboardCore\Support;

use Filament\Facades\Filament;
use Illuminate\Support\Str;

class RedirectPaths
{
    public static function isLocal(string $path): bool
    {
        if (! preg_match('~^/[\p{L}\p{N}_./-]*$~u', $path) || str_contains($path, '//')) {
            return false;
        }

        return ! array_intersect(explode('/', $path), ['.', '..'])
            && ($path === '/' || ! str_ends_with($path, '/'));
    }

    public static function isExcluded(string $path): bool
    {
        $path = trim($path, '/');
        $prefixes = ['adm', 'admin', 'api', 'mcp', 'livewire', 'livewire-*', 'uupp', 'up', 'sanctum', '_debugbar', 'boost'];
        foreach (Filament::getPanels() as $panel) {
            $prefixes[] = trim($panel->getPath(), '/');
        }
        $prefixes = [...$prefixes, ...config('dashboard-redirects.except', [])];

        foreach ($prefixes as $prefix) {
            $prefix = trim($prefix, '/');
            if ($prefix === '' || Str::is([$prefix, $prefix.'/*'], $path)) {
                return true;
            }
        }

        return false;
    }
}
