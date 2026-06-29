<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('content_report_states', function (Blueprint $table) {
            $table->id()->comment('신고 대상 상태 ID');
            $table->string('target_type', 160)->comment('신고 대상 모델 클래스');
            $table->unsignedBigInteger('target_id')->comment('신고 대상 ID');
            $table->string('report_status', 30)->default('NONE')->comment('신고 처리 상태(NONE, REPORTED, AUTO_BLOCKED, ADMIN_HIDDEN, NORMAL_VISIBLE, REEXPOSED, VALID, INVALID)');
            $table->unsignedInteger('report_count')->default(0)->comment('누적 신고 수');
            $table->unsignedInteger('recent_hour_report_count')->default(0)->comment('정상노출 처리 이후 최근 1시간 신고 수');
            $table->unsignedInteger('normal_visible_count')->default(0)->comment('관리자 정상노출 처리 횟수');
            $table->timestamp('first_reported_at')->nullable()->comment('최초 신고 시각');
            $table->timestamp('last_reported_at')->nullable()->comment('마지막 신고 시각');
            $table->timestamp('auto_blocked_at')->nullable()->comment('자동차단 시각');
            $table->timestamp('admin_hidden_at')->nullable()->comment('관리자 노출중지 시각');
            $table->timestamp('normal_visible_at')->nullable()->comment('정상노출 처리 시각');

            $table->foreignId('processed_by')
                ->nullable()
                ->comment('마지막 처리 관리자 account_staffs ID')
                ->constrained('account_staffs')
                ->nullOnDelete();

            $table->string('process_reason', 500)->nullable()->comment('마지막 처리 사유');
            $table->string('warning_status', 20)->default('NONE')->comment('신고 경고 처리 상태(NONE, WARNED, IGNORED)');
            $table->timestamp('warning_processed_at')->nullable()->comment('경고/무시 처리 시각');

            $table->foreignId('warning_processed_by')
                ->nullable()
                ->comment('경고/무시 처리 관리자 account_staffs ID')
                ->constrained('account_staffs')
                ->nullOnDelete();

            $table->timestamps();

            $table->unique(['target_type', 'target_id'], 'content_report_states_target_unique');
            $table->index(['target_type', 'report_status', 'updated_at'], 'content_report_states_type_status_updated_idx');
            $table->index(['target_type', 'report_status', 'first_reported_at'], 'content_report_states_type_status_first_reported_idx');
            $table->index(['target_type', 'report_status', 'target_id'], 'content_report_states_type_status_target_idx');
            $table->index(['target_type', 'first_reported_at', 'id'], 'content_report_states_type_first_reported_id_idx');
            $table->index(['target_type', 'last_reported_at', 'id'], 'content_report_states_type_last_reported_id_idx');
            $table->index(['target_type', 'updated_at', 'id'], 'content_report_states_type_updated_id_idx');
            $table->index(['target_type', 'report_count', 'id'], 'content_report_states_type_report_count_id_idx');
            $table->index(['report_status', 'last_reported_at'], 'content_report_states_status_reported_idx');
            $table->index(['processed_by', 'updated_at'], 'content_report_states_processed_updated_idx');
            $table->index(['warning_status', 'warning_processed_at'], 'content_report_states_warning_processed_idx');
            $table->index(['target_type', 'warning_status', 'target_id'], 'content_report_states_type_warning_target_idx');
        });

        DB::statement("ALTER TABLE content_report_states COMMENT = '콘텐츠 신고 대상별 현재 상태'");
    }

    public function down(): void
    {
        Schema::dropIfExists('content_report_states');
    }
};
