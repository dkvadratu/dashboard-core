<?php

namespace Dkvadratu\DashboardCore\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class BoostCommand extends Command
{
    protected $signature = 'dashboard:boost';

    protected $description = 'Install or update Boost agent guidelines, skills and MCP configuration';

    public function handle(): int
    {
        if (! $this->getApplication()->has('boost:install')) {
            $this->error('Install the development dependency first: composer require --dev "laravel/boost:^2.10". Then run php artisan dashboard:boost.');

            return self::FAILURE;
        }
        $path = base_path('boost.json');
        $settings = File::exists($path) ? json_decode(File::get($path), true) : [];
        if (! is_array($settings)) {
            $this->error('boost.json is invalid. Fix it before running dashboard:boost.');

            return self::FAILURE;
        }
        if (empty($settings['agents'])) {
            $settings['agents'] = ['codex'];
            $this->info('Default agent: Codex. Run php artisan boost:install interactively to choose other agents.');
        }
        $settings['packages'] = array_values(array_unique([
            ...($settings['packages'] ?? []),
            'dkvadratu/dashboard-core',
        ]));
        File::put($path, json_encode($settings, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)."\n");

        if (! empty($settings['guidelines']) && ! empty($settings['skills']) && ! empty($settings['mcp'])) {
            return $this->call('boost:update', ['--no-interaction' => true]);
        }

        return $this->call('boost:install', ['--guidelines' => true, '--skills' => true, '--mcp' => true, '--no-interaction' => true]);
    }
}
