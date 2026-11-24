<?php

namespace Dkvadratu\DashboardCore\Console;

use App\Providers\Filament\AdminPanelProvider;
use Illuminate\Console\Command;
use Illuminate\Support\ServiceProvider;

class InstallCommand extends Command
{
    protected $signature = 'dashboard:install {--fresh : Adapt a fresh Laravel application, backing up its User and bootstrap files} {--without-boost : Skip development agent setup}';

    protected $description = 'Install application-owned dashboard scaffolding once';

    public function handle(): int
    {
        if (is_file(base_path('.dashboard-installed'))) {
            $this->info('Already installed. Application files were not changed. Update the package with Composer.');

            return $this->option('without-boost') ? self::SUCCESS : $this->call('dashboard:boost', ['--no-interaction' => true]);
        }

        if (! $this->option('fresh')) {
            $this->error('Use --fresh on a new Laravel application. Existing projects must integrate the stubs manually.');

            return self::FAILURE;
        }

        $stubs = __DIR__.'/../../stubs/';
        foreach (['app/Models/User.php' => 'original-user.php.stub', 'bootstrap/app.php' => 'original-bootstrap.php.stub', 'config/cache.php' => 'original-cache.php.stub'] as $target => $original) {
            if (! is_file(base_path($target)) || trim(file_get_contents(base_path($target))) !== trim(file_get_contents($stubs.$original))) {
                $this->error($target.' differs from the supported Laravel skeleton. Integrate the package stubs manually; no files changed.');

                return self::FAILURE;
            }
        }
        foreach (['app/Providers/Filament/AdminPanelProvider.php', 'config/dashboard.php', 'routes/api.php', ...array_values(InstallMcpCommand::files())] as $target) {
            if (file_exists(base_path($target))) {
                $this->error($target.' already exists. Integrate manually; no files changed.');

                return self::FAILURE;
            }
        }
        if (glob(database_path('migrations/*_add_dashboard_fields_to_users_table.php')) !== []) {
            $this->error('Dashboard user migration already exists. No files changed.');

            return self::FAILURE;
        }

        foreach (['app/Models/User.php', 'bootstrap/app.php', 'config/cache.php'] as $target) {
            if (! copy(base_path($target), base_path($target.'.dashboard-backup'))) {
                $this->error('Could not back up '.$target);

                return self::FAILURE;
            }
        }

        $files = [
            'User.php.stub' => 'app/Models/User.php',
            'bootstrap.php.stub' => 'bootstrap/app.php',
            'cache.php.stub' => 'config/cache.php',
            'AdminPanelProvider.php.stub' => 'app/Providers/Filament/AdminPanelProvider.php',
            'api.php.stub' => 'routes/api.php',
            'add_dashboard_fields_to_users_table.php.stub' => 'database/migrations/'.date('Y_m_d_His').'_add_dashboard_fields_to_users_table.php',
        ];
        foreach ($files as $stub => $target) {
            $this->copyFile($stubs.$stub, base_path($target));
        }
        $this->copyFile(__DIR__.'/../../config/dashboard.php', config_path('dashboard.php'));
        foreach (['README.md', 'CHANGELOG.md'] as $document) {
            if (! file_exists(base_path($document))) {
                $this->copyFile($stubs.$document.'.stub', base_path($document));
            }
        }
        foreach (['.env', '.env.example'] as $file) {
            if (! file_exists(base_path($file))) {
                continue;
            }
            $contents = file_get_contents(base_path($file));
            foreach (file($stubs.'dashboard.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
                $key = explode('=', $line, 2)[0];
                if (! preg_match('/^'.preg_quote($key, '/').'=/m', $contents)) {
                    $contents .= "\n".$line;
                }
            }
            file_put_contents(base_path($file), rtrim($contents)."\n");
        }
        $ignorePath = base_path('.gitignore');
        $ignores = is_file($ignorePath) ? file_get_contents($ignorePath) : '';
        if (! in_array('*.dashboard-backup', explode("\n", $ignores), true)) {
            file_put_contents($ignorePath, rtrim($ignores)."\n*.dashboard-backup\n");
        }
        ServiceProvider::addProviderToBootstrapFile(AdminPanelProvider::class);
        if ($this->call('dashboard:install-mcp', ['--no-interaction' => true]) !== self::SUCCESS) {
            return self::FAILURE;
        }
        file_put_contents(base_path('.dashboard-installed'), "1\n");
        $this->info('Installed. Next: php artisan migrate; php artisan filament:assets; php artisan make:filament-user; php artisan dashboard:admin email@example.com');
        $this->warn('Configure mail, allowed IPs and SENTRY_LARAVEL_DSN. Enable email verification through the verification email.');
        $this->line('For optional application settings, run php artisan dashboard:code-tips.');

        if (! $this->option('without-boost')) {
            return $this->call('dashboard:boost', ['--no-interaction' => true]);
        }

        return self::SUCCESS;
    }

    private function copyFile(string $source, string $target): void
    {
        if (! is_dir(dirname($target))) {
            mkdir(dirname($target), 0755, true);
        }
        if (! copy($source, $target)) {
            throw new \RuntimeException('Could not write '.$target);
        }
    }
}
