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
            $table->uuid('batch_uuid')->nullable()->comment('일괄 처리 묶음 UUID');
            $table->text('reason')->nullable()->comment('운영 처리 사유');
            $table->json('metadata')->nullable()->comment('추가 메타데이터');

            $table->timestamps();

            $table->index(['target_type', 'target_id', 'id'], 'operation_histories_target_idx');
            $table->index(['batch_uuid', 'created_at'], 'operation_histories_batch_created_idx');
            $table->index(['actor_kind', 'created_at'], 'operation_histories_actor_kind_created_idx');
            $table->index(['actor_type', 'actor_id'], 'operation_histories_actor_idx');
            $table->index(['action', 'created_at'], 'operation_histories_action_created_idx');
        });

        Schema::create('operation_history_changes', function (Blueprint $table) {
            $table->id()->comment('운영 히스토리 변경 상세 ID');
            $table->foreignId('operation_history_id')
                ->comment('운영 히스토리 ID')
                ->constrained('operation_histories')
                ->cascadeOnDelete();
            $table->string('field_key', 120)->comment('변경 필드 키');
            $table->string('field_label', 120)->comment('변경 필드 표시명');
            $table->json('before_value')->nullable()->comment('변경 전 원본 값');
            $table->json('after_value')->nullable()->comment('변경 후 원본 값');
            $table->text('before_display')->nullable()->comment('변경 전 표시값');
            $table->text('after_display')->nullable()->comment('변경 후 표시값');
            $table->unsignedSmallInteger('sort_order')->default(0)->comment('표시 순서');
            $table->timestamps();

            $table->index(['operation_history_id', 'sort_order'], 'operation_history_changes_history_sort_idx');
            $table->index(['field_key'], 'operation_history_changes_field_key_idx');
        });

        DB::statement("ALTER TABLE operation_histories COMMENT = '운영 히스토리'");
        DB::statement("ALTER TABLE operation_history_changes COMMENT = '운영 히스토리 변경 상세'");
    }

    public function down(): void
    {
        Schema::dropIfExists('operation_history_changes');
        Schema::dropIfExists('operation_histories');
    }
};
