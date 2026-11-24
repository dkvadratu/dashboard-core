<?php

namespace Dkvadratu\DashboardCore\Console;

use Illuminate\Console\Command;

class AdminCommand extends Command
{
    protected $signature = 'dashboard:admin {email} {--revoke : Remove administrator access}';

    protected $description = 'Grant or revoke dashboard access for an existing user';

    public function handle(): int
    {
        $model = config('auth.providers.users.model');
        $user = $model::query()->where('email', $this->argument('email'))->first();
        if ($user === null) {
            $this->error('User not found. Create one with make:filament-user first.');

            return self::FAILURE;
        }
        if (($user->is_mcp_service_account ?? false) && ! $this->option('revoke')) {
            $this->error('MCP service accounts cannot become administrators.');

            return self::FAILURE;
        }
        $user->forceFill(['is_admin' => ! $this->option('revoke')])->save();
        $this->info($this->option('revoke') ? 'Administrator access revoked.' : 'Administrator access granted. Email verification is still required.');

        return self::SUCCESS;
    }
}
