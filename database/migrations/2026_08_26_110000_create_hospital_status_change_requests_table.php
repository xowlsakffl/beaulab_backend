<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hospital_status_change_requests', function (Blueprint $table) {
            $table->id()->comment('병의원 운영상태 변경 신청 ID');
            $table->foreignId('hospital_id')
                ->comment('대상 병의원 ID')
                ->constrained('hospitals')
                ->restrictOnDelete();
            $table->string('previous_status', 30)->comment('신청 당시 병의원 상태');
            $table->string('target_status', 30)->comment('요청 병의원 상태');
            $table->string('status', 30)->default('PENDING')->comment('결재 상태');
            $table->text('reason')->comment('상태 변경 요청 사유');
            $table->text('decision_reason')->nullable()->comment('반려 또는 취소 사유');
            $table->foreignId('requested_by_staff_id')
                ->comment('신청 관리자 ID')
                ->constrained('account_staffs')
                ->restrictOnDelete();
            $table->foreignId('processed_by_staff_id')
                ->nullable()
                ->comment('결재 관리자 ID')
                ->constrained('account_staffs')
                ->nullOnDelete();
            $table->timestamp('processed_at')->nullable()->comment('결재 완료 일시');
            $table->timestamps();

            $table->index(['hospital_id', 'status', 'created_at'], 'h_status_req_hospital_status_idx');
            $table->index(['status', 'created_at', 'id'], 'h_status_req_status_created_idx');
            $table->index(['requested_by_staff_id', 'created_at'], 'h_status_req_requester_created_idx');
        });

        DB::statement("ALTER TABLE hospital_status_change_requests COMMENT = '병의원 운영상태 변경 신청 및 결재'");
        DB::statement("ALTER TABLE hospital_status_change_requests ADD CONSTRAINT h_status_req_target_chk CHECK (target_status IN ('ACTIVE', 'SUSPENDED'))");
        DB::statement("ALTER TABLE hospital_status_change_requests ADD CONSTRAINT h_status_req_state_chk CHECK (status IN ('PENDING', 'APPROVED', 'REJECTED', 'CANCELLED'))");
    }

    public function down(): void
    {
        Schema::dropIfExists('hospital_status_change_requests');
    }
};
