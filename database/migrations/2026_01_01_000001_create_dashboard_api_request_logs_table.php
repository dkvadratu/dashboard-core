<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dashboard_api_request_logs', function (Blueprint $table): void {
            $table->id();
            $table->string('method', 10);
            $table->string('path', 2048);
            $table->unsignedSmallInteger('status')->index();
            $table->ipAddress('ip_address')->nullable();
            $table->json('request_body')->nullable();
            $table->json('response_body')->nullable();
            $table->timestamps();
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dashboard_api_request_logs');
    }
};
