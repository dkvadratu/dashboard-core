<?php

namespace Dkvadratu\DashboardCore\Models;

use Illuminate\Database\Eloquent\Model;

class ApiRequestLog extends Model
{
    protected $table = 'dashboard_api_request_logs';

    protected $fillable = ['method', 'path', 'status', 'ip_address', 'request_body', 'response_body', 'request_type', 'request_headers', 'response_headers'];

    protected function casts(): array
    {
        return ['request_body' => 'array', 'response_body' => 'array', 'request_headers' => 'array', 'response_headers' => 'array'];
    }
}
