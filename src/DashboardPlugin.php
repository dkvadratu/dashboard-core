<?php

namespace Dkvadratu\DashboardCore;

use AchyutN\FilamentLogViewer\FilamentLogViewer;
use Dkvadratu\DashboardCore\Filament\Pages\AdminDashboard;
use Dkvadratu\DashboardCore\Filament\Pages\McpTokens;
use Dkvadratu\DashboardCore\Filament\Pages\ProjectReadme;
use Dkvadratu\DashboardCore\Filament\Resources\ApiRequestLogs\ApiRequestLogResource;
use Dkvadratu\DashboardCore\Filament\Resources\QueuedJobs\QueuedJobResource;
use Dkvadratu\DashboardCore\Filament\Resources\Redirects\RedirectResource;
use Dkvadratu\DashboardCore\Http\Middleware\EnsureAdminIpAllowed;
use Dkvadratu\DashboardCore\Http\Middleware\EnsureDashboardAdmin;
use Filament\Auth\MultiFactor\App\AppAuthentication;
use Filament\Auth\MultiFactor\Email\EmailAuthentication;
use Filament\Contracts\Plugin;
use Filament\Http\Middleware\Authenticate;
use Filament\Panel;
use Filament\View\PanelsRenderHook;
use Illuminate\Contracts\View\View;

class DashboardPlugin implements Plugin
{
    public static function make(): static
    {
        return app(static::class);
    }

    public function getId(): string
    {
        return 'dashboard-core';
    }

    public function register(Panel $panel): void
    {
        $panel
            ->profile(isSimple: false)
            ->emailVerification()
            ->multiFactorAuthentication([
                AppAuthentication::make()->recoverable(),
                EmailAuthentication::make(),
            ], isRequired: (bool) config('dashboard.mfa_required'))
            ->middleware([EnsureAdminIpAllowed::class], isPersistent: true)
            ->authMiddleware([Authenticate::class, EnsureDashboardAdmin::class], isPersistent: true)
            ->pages([AdminDashboard::class, ProjectReadme::class, McpTokens::class])
            ->renderHook(PanelsRenderHook::SIDEBAR_FOOTER, fn (): View => view('dashboard-core::sidebar-footer'))
            ->resources([QueuedJobResource::class, ApiRequestLogResource::class])
            ->plugin(FilamentLogViewer::make());

        if (config('dashboard-redirects.enabled', false)) {
            $panel->resources([RedirectResource::class]);
        }
    }

    public function boot(Panel $panel): void {}
}
