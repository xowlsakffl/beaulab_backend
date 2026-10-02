<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hospital_chatbot_consultations', function (Blueprint $table) {
            $table->id()->comment('상담 ID');
            $table->foreignId('hospital_id')->comment('상담을 요청한 병의원 ID');
            $table->string('hospital_name_snapshot', 191)->nullable()->comment('요청 당시 병의원 표시명');
            $table->foreignId('requested_by_account_hospital_id')
                ->nullable()
                ->comment('요청한 병의원 계정 ID. 감사 용도이며 상담 소유권을 결정하지 않는다');
            $table->string('requester_name_snapshot', 191)->nullable()->comment('요청 당시 요청자 표시명');
            $table->string('status', 20)->comment('상담 상태(WAITING, CONNECTED, ENDED, CANCELED)');

            // MySQL은 Partial Unique Index를 지원하지 않는다.
            // 활성(WAITING·CONNECTED) 동안에만 hospital_id를 복사해 두고 종료·취소 시 NULL로 비워
            // 병의원당 활성 상담 1건을 DB 레벨에서 보장한다(PRD D-26).
            $table->unsignedBigInteger('active_hospital_id')
                ->nullable()
                ->comment('활성 상담일 때만 채워지는 병의원 ID. 병의원당 활성 상담 1건 보장용');

            $table->foreignId('assigned_staff_id')->nullable()->comment('연결된 상담원 직원 계정 ID');
            $table->string('assigned_staff_name_snapshot', 191)->nullable()->comment('연결 당시 상담원 표시명');

            $table->timestamp('requested_at')->comment('상담 요청 시각');
            $table->timestamp('connected_at')->nullable()->comment('상담원 연결 시각');
            $table->timestamp('ended_at')->nullable()->comment('상담 종료 시각');
            $table->timestamp('canceled_at')->nullable()->comment('상담 취소 시각');
            $table->string('close_reason', 40)->nullable()->comment('종료·취소 사유 코드');

            $table->timestamp('last_message_at')->nullable()->comment('마지막 메시지 시각');
            $table->unsignedBigInteger('last_message_sequence')->default(0)->comment('마지막 메시지 순번');

            $table->string('source_application', 100)->nullable()->comment('요청 Host 정보');
            $table->string('source_page_path', 255)->nullable()->comment('요청 당시 화면 경로');

            $table->unsignedBigInteger('version')->default(0)->comment('Optimistic Lock 버전');
            $table->timestamps();

            // 인덱스 이름을 직접 지정한다. 테이블명이 길어 자동 생성 이름이 MySQL의 64자 제한을 넘는다.
            $table->foreign('hospital_id', 'hcc_hospital_fk')
                ->references('id')->on('hospitals')->cascadeOnDelete();
            $table->foreign('requested_by_account_hospital_id', 'hcc_requester_fk')
                ->references('id')->on('account_hospitals')->nullOnDelete();
            $table->foreign('assigned_staff_id', 'hcc_assigned_staff_fk')
                ->references('id')->on('account_staffs')->nullOnDelete();

            $table->unique('active_hospital_id', 'hcc_active_hospital_unique');
            $table->index(['status', 'requested_at'], 'hcc_status_requested_at_index');
            $table->index(['assigned_staff_id', 'status', 'last_message_at'], 'hcc_agent_status_last_message_index');
            $table->index(['hospital_id', 'status'], 'hcc_hospital_status_index');
            $table->index('requested_at', 'hcc_requested_at_index');
            $table->index(['hospital_id', 'requested_at'], 'hcc_hospital_requested_at_index');
            $table->index(['assigned_staff_id', 'requested_at'], 'hcc_agent_requested_at_index');
        });

        DB::statement("ALTER TABLE hospital_chatbot_consultations COMMENT = '병의원 챗봇 상담원 연결 세션'");
    }

    public function down(): void
    {
        Schema::dropIfExists('hospital_chatbot_consultations');
    }
};
