<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('operation_histories', function (Blueprint $table) {
            $table->id()->comment('운영 히스토리 ID');

            $table->string('target_type', 255)->comment('대상 타입');
            $table->unsignedBigInteger('target_id')->comment('대상 ID');

            $table->string('actor_type', 255)->nullable()->comment('수행자 actor 타입');
            $table->unsignedBigInteger('actor_id')->nullable()->comment('수행자 actor ID');
            $table->string('actor_kind', 40)->comment('수행 주체 구분(STAFF, SYSTEM 등)');

            $table->string('action', 80)->comment('수행 액션');
            $table->string('field', 80)->nullable()->comment('변경 필드');
            $table->text('before_value')->nullable()->comment('변경 전 값');
            $table->text('after_value')->nullable()->comment('변경 후 값');
            $table->text('reason')->nullable()->comment('운영 처리 사유');
            $table->json('metadata')->nullable()->comment('추가 메타데이터');

            $table->timestamps();

            $table->index(['target_type', 'target_id', 'id'], 'operation_histories_target_idx');
            $table->index(['actor_kind', 'created_at'], 'operation_histories_actor_kind_created_idx');
            $table->index(['actor_type', 'actor_id'], 'operation_histories_actor_idx');
            $table->index(['action', 'created_at'], 'operation_histories_action_created_idx');
        });

        DB::statement("ALTER TABLE operation_histories COMMENT = '운영 히스토리'");
    }

    public function down(): void
    {
        Schema::dropIfExists('operation_histories');
    }
};
