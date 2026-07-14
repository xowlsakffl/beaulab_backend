<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hospital_event_ads', function (Blueprint $table) {
            $table->id()->comment('병의원 이벤트 광고 ID');

            $table->foreignId('hospital_id')
                ->comment('병의원 ID')
                ->constrained('hospitals')
                ->cascadeOnDelete();

            $table->foreignId('hospital_event_id')
                ->comment('이벤트 ID')
                ->constrained('hospital_events')
                ->cascadeOnDelete();

            $table->foreignId('manager_staff_id')
                ->nullable()
                ->comment('담당자 staff ID')
                ->constrained('account_staffs')
                ->nullOnDelete();

            $table->string('placement', 50)->comment('광고 위치');
            $table->unsignedBigInteger('cost')->default(0)->comment('광고 비용(P)');
            $table->timestamp('start_at')->comment('광고 시작 일시');
            $table->timestamp('end_at')->comment('광고 종료 일시');
            $table->string('allow_status', 20)->default('PENDING')->comment('검수 상태(신청, 검수, 승인, 반려)');

            $table->timestamps();
            $table->softDeletes()->comment('소프트 삭제 시각');

            $table->index(['created_at', 'id'], 'h_event_ads_created_id_idx');
            $table->index(['hospital_id', 'created_at'], 'h_event_ads_hospital_created_idx');
            $table->index(['hospital_event_id', 'created_at'], 'h_event_ads_event_created_idx');
            $table->index(['manager_staff_id', 'created_at'], 'h_event_ads_manager_created_idx');
            $table->index(['placement', 'start_at'], 'h_event_ads_slot_idx');
            $table->index(['allow_status', 'created_at'], 'h_event_ads_allow_created_idx');
            $table->index(['start_at', 'end_at'], 'h_event_ads_period_idx');
            $table->index(['deleted_at', 'id'], 'h_event_ads_deleted_id_idx');
            $table->index(['deleted_at', 'created_at', 'id'], 'h_event_ads_deleted_created_id_idx');
            $table->index(['deleted_at', 'start_at', 'id'], 'h_event_ads_deleted_start_id_idx');
            $table->index(['deleted_at', 'allow_status', 'id'], 'h_event_ads_deleted_allow_id_idx');
            $table->index(['deleted_at', 'placement', 'id'], 'h_event_ads_deleted_placement_id_idx');
        });

        DB::statement("ALTER TABLE hospital_event_ads COMMENT = '병의원 이벤트 광고'");
    }

    public function down(): void
    {
        Schema::dropIfExists('hospital_event_ads');
    }
};
