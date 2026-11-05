<?php

namespace Dkvadratu\DashboardCore\Console;

use Dkvadratu\DashboardCore\Models\ApiRequestLog;
use Illuminate\Console\Command;

class PruneLogsCommand extends Command
{
    protected $signature = 'dashboard:prune-logs';

    protected $description = 'Remove API logs older than the configured retention period';

    public function handle(): int
    {
        $deleted = ApiRequestLog::query()
            ->where('created_at', '<', now()->subDays(max(1, (int) config('dashboard.log_retention_days', 14))))
            ->delete();
        $this->info("Deleted {$deleted} API logs.");

        return self::SUCCESS;
    }
}
