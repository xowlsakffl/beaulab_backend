<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('admin_action_histories', function (Blueprint $table) {
            $table->id()->comment('운영자 액션 히스토리 ID');

            $table->string('target_type', 255)->comment('대상 타입');
            $table->unsignedBigInteger('target_id')->comment('대상 ID');

            $table->string('actor_type', 255)->nullable()->comment('수행자 actor 타입');
            $table->unsignedBigInteger('actor_id')->nullable()->comment('수행자 actor ID');

            $table->string('action', 80)->comment('수행 액션');
            $table->string('field', 80)->nullable()->comment('변경 필드');
            $table->text('before_value')->nullable()->comment('변경 전 값');
            $table->text('after_value')->nullable()->comment('변경 후 값');
            $table->text('reason')->nullable()->comment('운영 처리 사유');
            $table->json('metadata')->nullable()->comment('추가 메타데이터');

            $table->timestamps();

            $table->index(['target_type', 'target_id', 'id'], 'admin_action_histories_target_idx');
            $table->index(['actor_type', 'actor_id'], 'admin_action_histories_actor_idx');
            $table->index(['action', 'created_at'], 'admin_action_histories_action_created_idx');
        });

        DB::statement("ALTER TABLE admin_action_histories COMMENT = '운영자 액션 히스토리'");
    }

    public function down(): void
    {
        Schema::dropIfExists('admin_action_histories');
    }
};
