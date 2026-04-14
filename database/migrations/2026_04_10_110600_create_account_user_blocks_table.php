<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('account_user_blocks', function (Blueprint $table) {
            $table->id()->comment('사용자 차단 ID');

            $table->foreignId('blocker_user_id')
                ->comment('차단한 사용자 account_users ID')
                ->constrained('account_users')
                ->cascadeOnDelete();

            $table->foreignId('blocked_user_id')
                ->comment('차단된 사용자 account_users ID')
                ->constrained('account_users')
                ->cascadeOnDelete();

            $table->timestamp('blocked_at')
                ->comment('차단 시각');

            $table->timestamps();

            $table->unique(['blocker_user_id', 'blocked_user_id'], 'account_user_blocks_pair_unique');
            $table->index('blocked_user_id', 'account_user_blocks_blocked_idx');
        });

        DB::statement("ALTER TABLE account_user_blocks COMMENT = '앱 사용자 간 방향성 차단 관계'");
    }

    public function down(): void
    {
        Schema::dropIfExists('account_user_blocks');
    }
};
