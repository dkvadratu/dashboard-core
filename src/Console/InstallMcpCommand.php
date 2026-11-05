<?php

namespace Dkvadratu\DashboardCore\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class InstallMcpCommand extends Command
{
    protected $signature = 'dashboard:install-mcp';

    protected $description = 'Install the application-owned Users MCP example without overwriting existing files';

    /** @return array<string, string> */
    public static function files(): array
    {
        return [
            'UsersServer.php.stub' => 'app/Mcp/Servers/UsersServer.php',
            'ListUsers.php.stub' => 'app/Mcp/Tools/ListUsers.php',
            'CreateUser.php.stub' => 'app/Mcp/Tools/CreateUser.php',
            'ai.php.stub' => 'routes/ai.php',
        ];
    }

    public function handle(): int
    {
        if (is_file(base_path('.dashboard-mcp-installed'))) {
            $this->info('MCP already installed. Application files were not changed.');

            return self::SUCCESS;
        }
        foreach (self::files() as $target) {
            if (file_exists(base_path($target))) {
                $this->error($target.' already exists. Integrate the MCP stubs manually; no files changed.');

                return self::FAILURE;
            }
        }

        $files = self::files();
        if (glob(database_path('migrations/*_add_mcp_service_account_to_users_table.php')) === []) {
            $files['add_mcp_service_account_to_users_table.php.stub'] = 'database/migrations/'.date('Y_m_d_His').'_add_mcp_service_account_to_users_table.php';
        }
        foreach ($files as $stub => $target) {
            File::ensureDirectoryExists(dirname(base_path($target)));
            File::copy(__DIR__.'/../../stubs/'.$stub, base_path($target));
        }
        if (glob(database_path('migrations/*_create_personal_access_tokens_table.php')) === []) {
            if ($this->call('vendor:publish', ['--tag' => 'sanctum-migrations', '--no-interaction' => true]) !== self::SUCCESS) {
                return self::FAILURE;
            }
        }
        File::put(base_path('.dashboard-mcp-installed'), "1\n");
        $this->info('Users MCP installed. Run php artisan migrate, then create a token in the MCP tokens admin page.');
        $this->warn('Existing applications: add Laravel\\Sanctum\\HasApiTokens to User, cast is_mcp_service_account to boolean, and disallow service accounts in canAccessPanel. See README.');

        return self::SUCCESS;
    }
}
