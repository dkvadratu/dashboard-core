<?php

namespace Dkvadratu\DashboardCore\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class InstallRedirectsCommand extends Command
{
    protected $signature = 'dashboard:install-redirects';

    protected $description = 'Install optional public URL redirects configuration and migration';

    public function handle(): int
    {
        if (File::exists(base_path('.dashboard-redirects-installed'))) {
            $this->info('Redirects already installed. Application files were not changed.');

            return self::SUCCESS;
        }
        if (File::exists(config_path('dashboard-redirects.php')) || glob(database_path('migrations/*_create_dashboard_redirects_table.php')) !== []) {
            $this->error('Redirects configuration or migration already exists. Integrate manually; no files changed.');

            return self::FAILURE;
        }

        $files = [
            'dashboard-redirects.php.stub' => config_path('dashboard-redirects.php'),
            'create_dashboard_redirects_table.php.stub' => database_path('migrations/'.date('Y_m_d_His').'_create_dashboard_redirects_table.php'),
        ];
        foreach ($files as $stub => $target) {
            File::ensureDirectoryExists(dirname($target));
            if (! File::copy(__DIR__.'/../../stubs/'.$stub, $target)) {
                $this->error('Could not write '.$target);

                return self::FAILURE;
            }
        }
        File::put(base_path('.dashboard-redirects-installed'), "1\n");
        $this->info('Redirects installed. Run php artisan migrate and php artisan optimize:clear, then open Public web → Redirects.');
        $this->info('The package registers middleware automatically. Configure exclusions in config/dashboard-redirects.php.');

        return self::SUCCESS;
    }
}
