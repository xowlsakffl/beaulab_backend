<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('notification_deliveries', function (Blueprint $table): void {
            $table->unsignedInteger('generation')->default(0);
            $table->json('message_snapshot')->nullable();
            $table->json('device_results')->nullable();
            $table->uuid('lease_token')->nullable();
            $table->timestamp('processing_until')->nullable();
            $table->timestamp('next_attempt_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('notification_deliveries', function (Blueprint $table): void {
            $table->dropColumn(['generation', 'message_snapshot', 'device_results', 'lease_token', 'processing_until', 'next_attempt_at']);
        });
    }
};
