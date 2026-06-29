<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('content_reports', function (Blueprint $table) {
            $table->id()->comment('신고 ID');

            $table->foreignId('reporter_user_id')
                ->nullable()
                ->comment('신고자 account_users ID')
                ->constrained('account_users')
                ->nullOnDelete();

            $table->string('target_type', 160)->comment('신고 대상 모델 클래스');
            $table->unsignedBigInteger('target_id')->comment('신고 대상 ID');

            $table->foreignId('target_author_id')
                ->nullable()
                ->comment('신고 대상 작성자 account_users ID')
                ->constrained('account_users')
                ->nullOnDelete();

            $table->string('reason', 40)->comment('신고 사유');
            $table->string('reason_text', 500)->nullable()->comment('기타 신고 사유 직접 입력');
            $table->longText('content_snapshot')->nullable()->comment('신고 당시 대상 내용 스냅샷');
            $table->string('reporter_ip', 45)->nullable()->comment('신고자 IP');

            $table->timestamps();

            $table->index('reporter_user_id', 'content_reports_reporter_user_idx');
            $table->index(['target_type', 'target_id', 'created_at'], 'content_reports_target_created_idx');
            $table->index(['target_type', 'target_id', 'id'], 'content_reports_target_id_idx');
            $table->index(['target_type', 'target_id', 'reason'], 'content_reports_target_reason_idx');
            $table->index(['target_type', 'created_at'], 'content_reports_type_created_idx');
            $table->index(['reason', 'created_at'], 'content_reports_reason_created_idx');
            $table->index(['target_author_id', 'created_at'], 'content_reports_author_created_idx');
        });

        Schema::create('content_report_items', function (Blueprint $table) {
            $table->id()->comment('신고 대상 항목 ID');

            $table->foreignId('content_report_id')
                ->comment('콘텐츠 신고 ID')
                ->constrained('content_reports')
                ->cascadeOnDelete();

            $table->foreignId('reporter_user_id')
                ->nullable()
                ->comment('신고자 account_users ID')
                ->constrained('account_users')
                ->nullOnDelete();

            $table->string('target_type', 160)->comment('실제 신고 대상 모델 클래스');
            $table->unsignedBigInteger('target_id')->comment('실제 신고 대상 ID');

            $table->foreignId('target_author_id')
                ->nullable()
                ->comment('실제 신고 대상 작성자 account_users ID')
                ->constrained('account_users')
                ->nullOnDelete();

            $table->longText('content_snapshot')->nullable()->comment('신고 당시 항목 내용 스냅샷');

            $table->timestamps();

            // 테스트 중복 신고 확인을 위해 1인 1신고 unique 제약을 임시 비활성화한다.
            // 운영 기준 복구 시 아래 unique 제약으로 되돌리고 content_report_items_reporter_target_idx는 제거해야 한다.
            // $table->unique(['reporter_user_id', 'target_type', 'target_id'], 'content_report_items_reporter_target_unique');
            $table->index(['reporter_user_id', 'target_type', 'target_id'], 'content_report_items_reporter_target_idx');
            $table->index('content_report_id', 'content_report_items_report_idx');
            $table->index(['target_type', 'target_id', 'created_at'], 'content_report_items_target_created_idx');
            $table->index(['target_author_id', 'created_at'], 'content_report_items_author_created_idx');
        });

        DB::statement("ALTER TABLE content_reports COMMENT = '콘텐츠 신고 로그'");
        DB::statement("ALTER TABLE content_report_items COMMENT = '콘텐츠 신고 실제 대상 항목'");
    }

    public function down(): void
    {
        Schema::dropIfExists('content_report_items');
        Schema::dropIfExists('content_reports');
    }
};
