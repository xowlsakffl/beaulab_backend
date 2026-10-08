<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('staff_notification_outbox', function (Blueprint $table) {
            $table->id();
            $table->string('event_key', 191)->collation('utf8mb4_bin')->unique();
            $table->char('request_hash', 64);
            $table->string('event_type', 100);
            $table->string('target_type', 100);
            $table->unsignedBigInteger('target_id');
            $table->unsignedBigInteger('actor_staff_id')->nullable();
            $table->json('payload');
            $table->json('recipient_ids');
            $table->timestamp('processed_at')->nullable();
            $table->timestamp('next_dispatch_at')->nullable();
            $table->unsignedInteger('attempts')->default(0);
            $table->string('last_error', 255)->nullable();
            $table->timestamps();
            $table->index(['processed_at', 'next_dispatch_at', 'id'], 'staff_notification_outbox_pending_idx');
            $table->index(['target_type', 'target_id']);
        });

        Schema::create('staff_notification_receipts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('outbox_id')->constrained('staff_notification_outbox')->restrictOnDelete();
            $table->unsignedBigInteger('staff_id');
            $table->unsignedBigInteger('notification_inbox_id')->nullable();
            $table->unsignedBigInteger('message_id')->nullable();
            $table->string('status', 20);
            $table->timestamp('created_at');
            $table->unique(['outbox_id', 'staff_id']);
            $table->index(['staff_id', 'id']);
            $table->index(['notification_inbox_id', 'id'], 'staff_notification_receipts_inbox_idx');
        });

        Schema::create('staff_notification_states', function (Blueprint $table) {
            $table->unsignedBigInteger('staff_id')->primary();
            $table->unsignedBigInteger('version')->default(0);
            $table->unsignedBigInteger('broadcast_version')->default(0);
            $table->timestamp('next_dispatch_at')->nullable();
            $table->timestamps();
        });

    }

    public function down(): void
    {
        Schema::dropIfExists('staff_notification_states');
        Schema::dropIfExists('staff_notification_receipts');
        Schema::dropIfExists('staff_notification_outbox');
    }
};
