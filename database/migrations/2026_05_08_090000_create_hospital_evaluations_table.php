<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hospital_evaluations', function (Blueprint $table) {
            $table->id()->comment('병의원 평가 ID');

            $table->foreignId('author_id')
                ->nullable()
                ->comment('작성자 account_users ID')
                ->constrained('account_users')
                ->nullOnDelete();

            $table->foreignId('hospital_id')
                ->comment('병의원 ID')
                ->constrained('hospitals')
                ->cascadeOnDelete();

            $table->foreignId('doctor_id')
                ->nullable()
                ->comment('의료진 hospital_doctors ID')
                ->constrained('hospital_doctors')
                ->nullOnDelete();

            $table->longText('content')->comment('평가 내용');
            $table->string('phone', 30)->nullable()->comment('유저 연락처');
            $table->string('author_ip', 45)->nullable()->comment('작성 IP');
            $table->unsignedInteger('cost')->default(0)->comment('시술/수술 비용(만원 단위)');

            $table->unsignedTinyInteger('rating_staff_kindness')->comment('직원친절도 평점(1~5)');
            $table->unsignedTinyInteger('rating_surgery_satisfaction')->comment('수술만족도 평점(1~5)');
            $table->unsignedTinyInteger('rating_facility')->comment('병원시설 평점(1~5)');
            $table->unsignedTinyInteger('rating_aftercare')->comment('사후관리 평점(1~5)');
            $table->unsignedTinyInteger('rating_cost')->comment('비용 평점(1~5)');
            $table->decimal('average_rating', 2, 1)->default(0)->comment('5개 평점 항목 평균');

            $table->boolean('has_overtreatment')->comment('과잉진료 여부(false=없음, true=있음)');
            $table->boolean('is_waiting_time_long')->comment('대기시간 평가(false=짧았음, true=길었음)');
            $table->boolean('has_doctor_consultation')->comment('지정의사 상담 여부(false=상담안함, true=상담함)');
            $table->boolean('is_recommended')->comment('지인 추천 여부(false=비추천, true=추천)');

            $table->string('status', 20)->default('ACTIVE')->comment('노출상태(ACTIVE, INACTIVE)');
            $table->string('post_status', 30)->default('POST_NORMAL')->comment('게시상태(POST_NORMAL, POST_AUTO_BLIND, POST_USER_DELETE, POST_ADMIN_STOP)');
            $table->unsignedBigInteger('view_count')->default(0)->comment('조회수');

            $table->string('receipt_status', 30)->default('NONE')->comment('영수증 상태(NONE, UPLOADED, VERIFIED, REJECTED)');
            $table->string('receipt_rejection_reason', 80)->nullable()->comment('영수증 부적합 사유(IMAGE_MISMATCH, BUSINESS_NAME_MISMATCH, BUSINESS_NUMBER_MISMATCH, TRANSACTION_DATE_MISMATCH, SURGERY_COST_MISMATCH, OTHER)');
            $table->text('receipt_rejection_reason_text')->nullable()->comment('영수증 부적합 기타 직접입력 사유');

            $table->timestamps();
            $table->softDeletes()->comment('소프트 삭제 시각');

            $table->index(['deleted_at', 'id'], 'h_evaluations_deleted_id_idx');
            $table->index(['deleted_at', 'created_at', 'id'], 'h_evaluations_deleted_created_id_idx');
            $table->index(['deleted_at', 'updated_at', 'id'], 'h_evaluations_deleted_updated_id_idx');
            $table->index(['deleted_at', 'status', 'id'], 'h_evaluations_deleted_status_id_idx');
            $table->index(['deleted_at', 'post_status', 'status', 'created_at', 'id'], 'h_evals_deleted_post_status_created_idx');
            $table->index(['deleted_at', 'receipt_status', 'created_at', 'id'], 'h_evals_deleted_receipt_created_idx');
            $table->index(['deleted_at', 'author_id', 'created_at', 'id'], 'h_evals_deleted_author_created_idx');
            $table->index(['deleted_at', 'hospital_id', 'created_at', 'id'], 'h_evals_deleted_hospital_created_idx');
            $table->index(['deleted_at', 'doctor_id', 'created_at', 'id'], 'h_evals_deleted_doctor_created_idx');
            $table->index(['deleted_at', 'cost', 'created_at', 'id'], 'h_evals_deleted_cost_created_idx');
            $table->index(['deleted_at', 'average_rating', 'created_at', 'id'], 'h_evals_deleted_rating_created_idx');
            $table->index(['deleted_at', 'view_count', 'created_at', 'id'], 'h_evals_deleted_view_created_idx');
        });

        DB::statement("ALTER TABLE hospital_evaluations COMMENT = '병의원 평가'");
    }

    public function down(): void
    {
        Schema::dropIfExists('hospital_evaluations');
    }
};
