<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('dashboard_api_request_logs', function (Blueprint $table): void {
            $table->string('request_type', 4)->default('api')->index();
            $table->json('request_headers')->nullable();
            $table->json('response_headers')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('dashboard_api_request_logs', function (Blueprint $table): void {
            $table->dropIndex(['request_type']);
            $table->dropColumn(['request_type', 'request_headers', 'response_headers']);
        });
    }
};
