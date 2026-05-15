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

            // TODO: 테스트 기간에는 동일 유저가 같은 콘텐츠를 여러 번 신고할 수 있게 허용한다.
            // 운영 정책 확정 시 unique 제약을 복구해야 한다.
            // $table->unique(['reporter_user_id', 'target_type', 'target_id'], 'content_reports_reporter_target_unique');
            $table->index('reporter_user_id', 'content_reports_reporter_user_idx');
            $table->index(['target_type', 'target_id', 'created_at'], 'content_reports_target_created_idx');
            $table->index(['target_type', 'target_id', 'reason'], 'content_reports_target_reason_idx');
            $table->index(['target_type', 'created_at'], 'content_reports_type_created_idx');
            $table->index(['reason', 'created_at'], 'content_reports_reason_created_idx');
            $table->index(['target_author_id', 'created_at'], 'content_reports_author_created_idx');
        });

        DB::statement("ALTER TABLE content_reports COMMENT = '콘텐츠 신고 로그'");
    }

    public function down(): void
    {
        Schema::dropIfExists('content_reports');
    }
};
