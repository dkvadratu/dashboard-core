<?php

return [
    'panel_id' => 'admin',
    'allowed_ips' => array_values(array_filter(array_map('trim', explode(',', (string) env('DASHBOARD_ALLOWED_IPS', '127.0.0.1,::1'))))),
    // Preserve existing installations; new projects enable MCP through dashboard:install-mcp.
    'mcp_enabled' => is_file(base_path('.dashboard-mcp-installed')),
    'mcp_token_abilities' => ['users:read' => 'List users', 'users:create' => 'Create users'],
    'mcp_user_email' => 'dashboard-ai@example.invalid',
    'version' => env('APP_VERSION', '0.1.0'),
    'readme' => base_path('README.md'),
    'changelog' => base_path('CHANGELOG.md'),
    'log_api_requests' => env('DASHBOARD_LOG_API', false),
    'log_http_requests' => env('DASHBOARD_LOG_HTTP', false),
    'log_detailed' => env('DASHBOARD_LOGS_DETAILED', false),
    'log_retention_days' => 14,
    'redacted_keys' => ['password', 'password_confirmation', 'token', 'access_token', 'refresh_token', 'authorization', 'cookie', 'secret', 'api_key', 'api_token', 'client_secret', '_token', 'csrf_token', 'snapshot', 'html', 'code', 'recovery_code'],
];
