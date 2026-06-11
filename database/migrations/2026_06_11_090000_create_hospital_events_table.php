<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hospital_events', function (Blueprint $table) {
            $table->id()->comment('병의원 이벤트 ID');

            $table->foreignId('hospital_id')
                ->comment('소속 병의원 ID')
                ->constrained('hospitals')
                ->cascadeOnDelete();

            $table->string('event_type', 20)->comment('이벤트 유형(TEXT, IMAGE)');

            $table->string('name', 20)->comment('이벤트명');
            $table->string('description', 40)->comment('이벤트 설명');

            $table->boolean('is_event_period_unlimited')->default(true)->comment('이벤트 기간 무제한 여부');
            $table->timestamp('event_start_at')->nullable()->comment('이벤트 시작 일시');
            $table->timestamp('event_end_at')->nullable()->comment('이벤트 종료 일시');

            $table->unsignedBigInteger('normal_price')->default(0)->comment('정상 가격(원)');
            $table->unsignedBigInteger('event_price')->default(0)->comment('이벤트 가격(원)');
            $table->boolean('is_vat_included')->default(true)->comment('VAT 포함 여부');
            $table->decimal('discount_rate', 5, 1)->default(0)->comment('할인율(%, 소수점 1자리)');

            $table->unsignedBigInteger('base_consultation_price')->default(0)->comment('이벤트 가격 기준 상담 신청 기준 단가(원)');
            $table->unsignedBigInteger('consultation_price')->default(0)->comment('상담 신청 단가(원)');

            $table->boolean('has_options')->default(false)->comment('이벤트 옵션 사용 여부');
            $table->json('procedure_targets')->nullable()->comment('텍스트형 시술 대상 목록');
            $table->json('procedure_benefits')->nullable()->comment('텍스트형 시술 장점 목록');
            $table->string('side_effect_notice', 90)->nullable()->comment('부작용 안내');

            // 이벤트 이미지는 media 테이블(collection)로 분리 관리
            // - thumbnail_image
            // - event_page_image

            $table->string('allow_status', 20)->default('DRAFT')->comment('검수 상태(DRAFT, PENDING, REVIEWING, APPROVED, REJECTED, PARTNER_CANCELED)');
            $table->string('status', 20)->default('INACTIVE')->comment('노출 상태(ACTIVE, INACTIVE)');

            $table->unsignedBigInteger('view_count')->default(0)->comment('조회수');

            $table->timestamps();
            $table->softDeletes()->comment('소프트 삭제 시각');

            $table->index(['hospital_id', 'created_at'], 'h_events_hospital_created_idx');
            $table->index(['hospital_id', 'status', 'created_at'], 'h_events_hospital_status_created_idx');
            $table->index(['event_type', 'created_at'], 'h_events_type_created_idx');
            $table->index(['allow_status', 'created_at'], 'h_events_allow_status_created_idx');
            $table->index(['status', 'event_start_at', 'event_end_at'], 'h_events_status_period_idx');
            $table->index(['normal_price', 'event_price'], 'h_events_price_idx');
            $table->index('discount_rate');
            $table->index('view_count');
        });

        DB::statement("ALTER TABLE hospital_events COMMENT = '병의원 이벤트'");

        Schema::create('hospital_event_doctor_assignments', function (Blueprint $table) {
            $table->id()->comment('병의원 이벤트 의료진 연결 ID');

            $table->foreignId('hospital_event_id')
                ->comment('병의원 이벤트 ID')
                ->constrained('hospital_events')
                ->cascadeOnDelete();

            $table->foreignId('hospital_doctor_id')
                ->comment('의료진 ID')
                ->constrained('hospital_doctors')
                ->cascadeOnDelete();

            $table->unsignedTinyInteger('sort_order')->default(0)->comment('노출 순서');
            $table->boolean('is_career_visible')->default(true)->comment('경력사항 노출 여부');
            $table->boolean('is_activity_visible')->default(false)->comment('활동사항 노출 여부');

            $table->timestamps();

            $table->unique(['hospital_event_id', 'hospital_doctor_id'], 'h_event_doctor_assignments_unique');
            $table->index(['hospital_event_id', 'sort_order'], 'h_event_doctor_assignments_event_sort_idx');
            $table->index('hospital_doctor_id', 'h_event_doctor_assignments_doctor_idx');
        });

        DB::statement("ALTER TABLE hospital_event_doctor_assignments COMMENT = '병의원 이벤트 의료진 매핑'");

        Schema::create('hospital_event_options', function (Blueprint $table) {
            $table->id()->comment('병의원 이벤트 옵션 ID');

            $table->foreignId('hospital_event_id')
                ->comment('병의원 이벤트 ID')
                ->constrained('hospital_events')
                ->cascadeOnDelete();

            $table->unsignedInteger('sort_order')->default(0)->comment('옵션 노출 순서');
            $table->string('name', 40)->comment('옵션명');
            $table->unsignedSmallInteger('session_count')->default(1)->comment('회차');
            $table->unsignedBigInteger('normal_price')->default(0)->comment('옵션 정가(원)');
            $table->unsignedBigInteger('event_price')->default(0)->comment('옵션 할인가(원)');
            $table->decimal('discount_rate', 5, 1)->default(0)->comment('옵션 할인율(%, 소수점 1자리)');

            $table->timestamps();

            $table->index(['hospital_event_id', 'sort_order'], 'h_event_options_event_sort_idx');
            $table->index(['hospital_event_id', 'id'], 'h_event_options_event_id_idx');
        });

        DB::statement("ALTER TABLE hospital_event_options COMMENT = '병의원 이벤트 옵션'");
    }

    public function down(): void
    {
        Schema::dropIfExists('hospital_event_options');
        Schema::dropIfExists('hospital_event_doctor_assignments');
        Schema::dropIfExists('hospital_events');
    }
};
