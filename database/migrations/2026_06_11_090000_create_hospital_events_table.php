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
            $table->boolean('is_male_targeted')->default(false)->comment('남자성형 이벤트 여부');

            $table->string('name', 20)->comment('이벤트명');
            $table->string('description', 40)->comment('이벤트 설명');

            $table->boolean('is_event_period_unlimited')->default(true)->comment('이벤트 기간 무제한 여부');
            $table->timestamp('event_start_at')->nullable()->comment('이벤트 시작 일시');
            $table->timestamp('event_end_at')->nullable()->comment('이벤트 종료 일시');

            $table->unsignedBigInteger('normal_price')->default(0)->comment('정상 가격(원)');
            $table->unsignedBigInteger('event_price')->default(0)->comment('이벤트 가격(원)');
            $table->boolean('is_vat_included')->default(true)->comment('VAT 포함 여부');
            $table->unsignedTinyInteger('discount_rate')->default(0)->comment('할인율(%, 정수)');

            $table->unsignedBigInteger('base_consultation_price')->default(0)->comment('이벤트 가격 기준 상담 신청 기준 단가(원)');
            $table->unsignedBigInteger('consultation_price')->default(0)->comment('상담 신청 단가(원)');

            $table->boolean('has_options')->default(false)->comment('이벤트 옵션 사용 여부');
            $table->json('procedure_targets')->nullable()->comment('텍스트형 시술 대상 목록');
            $table->json('procedure_benefits')->nullable()->comment('텍스트형 시술 장점 목록');
            $table->string('side_effect_notice', 90)->nullable()->comment('부작용 안내');

            // 이벤트 이미지는 media 테이블(collection)로 분리 관리
            // - thumbnail_image
            // - event_page_image

            $table->string('allow_status', 20)->default('PENDING')->comment('검수 상태(신청, 검수, 승인, 반려)');
            $table->string('hospital_status', 20)->default('PUBLIC')->comment('병원 공개 상태(공개, 비공개)');
            $table->string('admin_status', 20)->default('NORMAL')->comment('관리자 운영 상태(정상, 강제중지)');

            $table->unsignedBigInteger('view_count')->default(0)->comment('조회수');

            $table->timestamps();
            $table->softDeletes()->comment('소프트 삭제 시각');

            $table->index(['hospital_id', 'created_at'], 'h_events_hospital_created_idx');
            $table->index(['hospital_id', 'hospital_status', 'admin_status', 'created_at'], 'h_events_hospital_state_created_idx');
            $table->index(['event_type', 'created_at'], 'h_events_type_created_idx');
            $table->index(['allow_status', 'created_at'], 'h_events_allow_status_created_idx');
            $table->index(['hospital_status', 'admin_status', 'event_start_at', 'event_end_at'], 'h_events_state_period_idx');
            $table->index(['normal_price', 'event_price'], 'h_events_price_idx');
            $table->index('discount_rate');
            $table->index('view_count');
            $table->index(['deleted_at', 'id'], 'h_events_deleted_id_idx');
            $table->index(['deleted_at', 'created_at', 'id'], 'h_events_deleted_created_id_idx');
            $table->index(['deleted_at', 'updated_at', 'id'], 'h_events_deleted_updated_id_idx');
            $table->index(['deleted_at', 'event_start_at', 'id'], 'h_events_deleted_start_id_idx');
            $table->index(['deleted_at', 'event_end_at', 'id'], 'h_events_deleted_end_id_idx');
            $table->index(['deleted_at', 'hospital_status', 'id'], 'h_events_deleted_hospital_status_id_idx');
            $table->index(['deleted_at', 'admin_status', 'id'], 'h_events_deleted_admin_status_id_idx');
            $table->index(['deleted_at', 'allow_status', 'id'], 'h_events_deleted_allow_status_id_idx');
            $table->index(['deleted_at', 'event_type', 'id'], 'h_events_deleted_type_id_idx');
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
            $table->unsignedTinyInteger('discount_rate')->default(0)->comment('옵션 할인율(%, 정수)');

            $table->timestamps();

            $table->index(['hospital_event_id', 'sort_order'], 'h_event_options_event_sort_idx');
            $table->index(['hospital_event_id', 'id'], 'h_event_options_event_id_idx');
        });

        DB::statement("ALTER TABLE hospital_event_options COMMENT = '병의원 이벤트 옵션'");

        Schema::create('hospital_event_dbs', function (Blueprint $table) {
            $table->id()->comment('병의원 이벤트 DB ID');

            $table->foreignId('account_user_id')
                ->comment('신청 일반회원 account_users ID')
                ->constrained('account_users')
                ->cascadeOnDelete();

            $table->foreignId('hospital_id')
                ->comment('병의원 ID')
                ->constrained('hospitals')
                ->cascadeOnDelete();

            $table->foreignId('hospital_event_id')
                ->comment('병의원 이벤트 ID')
                ->constrained('hospital_events')
                ->cascadeOnDelete();

            $table->foreignId('hospital_doctor_id')
                ->nullable()
                ->comment('선택 의료진 ID')
                ->constrained('hospital_doctors')
                ->nullOnDelete();

            $table->string('name', 50)->comment('신청자 이름');
            $table->string('phone', 30)->comment('신청자 전화번호');
            $table->string('phone_normalized', 30)->comment('검색/중복판정용 정규화 전화번호');
            $table->string('contact_method', 20)->comment('연락수단(KAKAO, PHONE, SMS)');
            $table->string('preferred_time', 20)->comment('선호시간(MORNING, AFTERNOON, ANYTIME)');

            $table->unsignedBigInteger('event_price')->default(0)->comment('신청 시점 이벤트 가격(원)');
            $table->unsignedBigInteger('consultation_price')->default(0)->comment('신청 시점 소진 단가(원)');

            $table->string('status', 20)->default('NEW')->comment('상담 여부 상태(NEW, CONFIRMED, DUPLICATE)');
            $table->string('allow_status', 30)->default('NORMAL_CONFIRMED')->comment('검증 상태(UNVERIFIED_REPORTED, UNVERIFIED_CONFIRMED, NORMAL_CONFIRMED)');

            $table->timestamp('contacted_at')->nullable()->comment('연락 처리 시각');
            $table->timestamp('confirmed_at')->nullable()->comment('상담 확인 처리 시각');
            $table->timestamp('duplicated_at')->nullable()->comment('중복 처리 시각');

            $table->string('author_ip', 45)->nullable()->comment('신청 IP(v4/v6)');
            $table->string('user_agent', 500)->nullable()->comment('신청 User-Agent');
            $table->timestamp('privacy_agreed_at')->nullable()->comment('개인정보 수집/이용 동의 시각');
            $table->timestamp('marketing_agreed_at')->nullable()->comment('마케팅 수신 동의 시각');

            $table->timestamps();
            $table->softDeletes()->comment('소프트 삭제 시각');

            $table->index(['created_at', 'id'], 'h_event_consults_created_id_idx');
            $table->index(['account_user_id', 'created_at'], 'h_event_consults_user_created_idx');
            $table->index(['hospital_id', 'created_at'], 'h_event_consults_hospital_created_idx');
            $table->index(['hospital_event_id', 'created_at'], 'h_event_consults_event_created_idx');
            $table->index(['hospital_event_id', 'phone_normalized', 'name'], 'h_event_consults_event_phone_name_idx');
            $table->index(['hospital_doctor_id', 'created_at'], 'h_event_consults_doctor_created_idx');
            $table->index(['contact_method', 'created_at'], 'h_event_consults_contact_created_idx');
            $table->index(['preferred_time', 'created_at'], 'h_event_consults_preferred_created_idx');
            $table->index(['status', 'created_at'], 'h_event_consults_status_created_idx');
            $table->index(['allow_status', 'created_at'], 'h_event_consults_allow_status_created_idx');
            $table->index(['event_price', 'consultation_price'], 'h_event_consults_amount_idx');
            $table->index(['hospital_event_id', 'status', 'consultation_price'], 'h_event_dbs_event_status_price_idx');
            $table->index(['deleted_at', 'id'], 'h_event_dbs_deleted_id_idx');
            $table->index(['deleted_at', 'created_at', 'id'], 'h_event_dbs_deleted_created_id_idx');
            $table->index(['deleted_at', 'updated_at', 'id'], 'h_event_dbs_deleted_updated_id_idx');
            $table->index(['deleted_at', 'account_user_id', 'id'], 'h_event_dbs_deleted_user_id_idx');
            $table->index(['deleted_at', 'hospital_id', 'id'], 'h_event_dbs_deleted_hospital_id_idx');
            $table->index(['deleted_at', 'hospital_event_id', 'id'], 'h_event_dbs_deleted_event_id_idx');
            $table->index(['deleted_at', 'hospital_doctor_id', 'id'], 'h_event_dbs_deleted_doctor_id_idx');
            $table->index(['deleted_at', 'status', 'id'], 'h_event_dbs_deleted_status_id_idx');
            $table->index(['deleted_at', 'allow_status', 'id'], 'h_event_dbs_deleted_allow_status_id_idx');
            $table->index('phone', 'h_event_consults_phone_idx');
            $table->index('phone_normalized', 'h_event_consults_phone_normalized_idx');
            $table->index('author_ip', 'h_event_consults_author_ip_idx');
        });

        DB::statement("ALTER TABLE hospital_event_dbs COMMENT = '병의원 이벤트 DB 신청'");

        Schema::create('hospital_event_real_model_dbs', function (Blueprint $table) {
            $table->id()->comment('병의원 이벤트 리얼모델 신청 ID');

            $table->foreignId('account_user_id')
                ->comment('신청 일반회원 account_users ID')
                ->constrained('account_users')
                ->cascadeOnDelete();

            $table->foreignId('hospital_id')
                ->comment('병의원 ID')
                ->constrained('hospitals')
                ->cascadeOnDelete();

            $table->foreignId('hospital_event_id')
                ->comment('병의원 이벤트 ID')
                ->constrained('hospital_events')
                ->cascadeOnDelete();

            $table->string('name', 50)->comment('신청자 이름');
            $table->string('gender', 10)->comment('성별(MALE, FEMALE)');
            $table->date('birth_date')->comment('생년월일');
            $table->string('phone', 30)->comment('신청자 전화번호');
            $table->string('phone_normalized', 30)->comment('검색용 정규화 전화번호');
            $table->unsignedSmallInteger('height_cm')->comment('키(cm)');
            $table->unsignedSmallInteger('weight_kg')->comment('몸무게(kg)');
            $table->string('surgery_period', 50)->comment('수술시기');
            $table->string('support_part', 100)->comment('지원부위');
            $table->string('instagram_url', 255)->nullable()->comment('인스타그램 주소');
            $table->string('blog_url', 255)->nullable()->comment('블로그 주소');
            $table->json('special_notes')->nullable()->comment('회원 특이사항 코드 목록');
            $table->text('application_reason')->comment('리얼모델 지원이유');
            $table->text('inquiry')->nullable()->comment('문의사항');
            $table->string('status', 20)->default('RECEIVED')->comment('승인상태(RECEIVED, APPROVED, REJECTED)');
            $table->string('author_ip', 45)->nullable()->comment('신청 IP(v4/v6)');
            $table->string('user_agent', 500)->nullable()->comment('신청 User-Agent');

            $table->timestamps();
            $table->softDeletes()->comment('소프트 삭제 시각');

            $table->index(['created_at', 'id'], 'h_event_real_models_created_id_idx');
            $table->index(['account_user_id', 'created_at'], 'h_event_real_models_user_created_idx');
            $table->index(['hospital_id', 'created_at'], 'h_event_real_models_hospital_created_idx');
            $table->index(['hospital_event_id', 'created_at'], 'h_event_real_models_event_created_idx');
            $table->index(['status', 'created_at'], 'h_event_real_models_status_created_idx');
            $table->index(['gender', 'created_at'], 'h_event_real_models_gender_created_idx');
            $table->index(['deleted_at', 'id'], 'h_event_real_dbs_deleted_id_idx');
            $table->index(['deleted_at', 'created_at', 'id'], 'h_event_real_dbs_deleted_created_id_idx');
            $table->index(['deleted_at', 'updated_at', 'id'], 'h_event_real_dbs_deleted_updated_id_idx');
            $table->index(['deleted_at', 'account_user_id', 'id'], 'h_event_real_dbs_deleted_user_id_idx');
            $table->index(['deleted_at', 'hospital_id', 'id'], 'h_event_real_dbs_deleted_hospital_id_idx');
            $table->index(['deleted_at', 'hospital_event_id', 'id'], 'h_event_real_dbs_deleted_event_id_idx');
            $table->index(['deleted_at', 'status', 'id'], 'h_event_real_dbs_deleted_status_id_idx');
            $table->index(['deleted_at', 'gender', 'id'], 'h_event_real_dbs_deleted_gender_id_idx');
            $table->index(['deleted_at', 'birth_date', 'id'], 'h_event_real_dbs_deleted_birth_id_idx');
            $table->index('phone', 'h_event_real_models_phone_idx');
            $table->index('phone_normalized', 'h_event_real_models_phone_normalized_idx');
        });

        DB::statement("ALTER TABLE hospital_event_real_model_dbs COMMENT = '병의원 이벤트 리얼모델 신청'");
    }

    public function down(): void
    {
        Schema::dropIfExists('hospital_event_real_model_dbs');
        Schema::dropIfExists('hospital_event_dbs');
        Schema::dropIfExists('hospital_event_options');
        Schema::dropIfExists('hospital_event_doctor_assignments');
        Schema::dropIfExists('hospital_events');
    }
};
