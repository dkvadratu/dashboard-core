<?php

namespace Dkvadratu\DashboardCore;

use Dkvadratu\DashboardCore\Console\AdminCommand;
use Dkvadratu\DashboardCore\Console\BoostCommand;
use Dkvadratu\DashboardCore\Console\CodeTipsCommand;
use Dkvadratu\DashboardCore\Console\InstallCommand;
use Dkvadratu\DashboardCore\Console\InstallMcpCommand;
use Dkvadratu\DashboardCore\Console\InstallRedirectsCommand;
use Dkvadratu\DashboardCore\Console\PruneLogsCommand;
use Dkvadratu\DashboardCore\Http\Middleware\RedirectRequests;
use Dkvadratu\DashboardCore\Listeners\LogApiRequest;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Contracts\Http\Kernel;
use Illuminate\Foundation\Http\Events\RequestHandled;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

class DashboardCoreServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/dashboard.php', 'dashboard');
    }

    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'dashboard-core');
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
        Event::listen(RequestHandled::class, LogApiRequest::class);

        if (config('dashboard-redirects.enabled', false)) {
            $this->callAfterResolving(Kernel::class, function (Kernel $kernel): void {
                $kernel->pushMiddleware(RedirectRequests::class);
            });
        }

        if ($this->app->runningInConsole()) {
            $this->commands([InstallCommand::class, InstallMcpCommand::class, InstallRedirectsCommand::class, BoostCommand::class, CodeTipsCommand::class, AdminCommand::class, PruneLogsCommand::class]);
        }
        $this->callAfterResolving(Schedule::class, function (Schedule $schedule): void {
            $schedule->command('dashboard:prune-logs')->daily()->withoutOverlapping();
        });
    }
}
