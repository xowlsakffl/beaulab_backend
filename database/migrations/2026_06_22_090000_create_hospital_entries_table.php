<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hospital_entries', function (Blueprint $table) {
            $table->id()->comment('병의원 입점신청 ID');

            $table->string('hospital_name', 100)->comment('병의원명');
            $table->string('hospital_phone', 30)->comment('병의원 전화번호');
            $table->string('address', 255)->comment('주소');
            $table->string('address_detail', 255)->nullable()->comment('상세주소');
            $table->string('business_number', 30)->comment('사업자등록번호');
            $table->string('ceo_name', 50)->comment('대표자명');
            $table->string('license_number', 50)->nullable()->comment('의사면허번호');

            // 입점신청 관련 파일은 media 테이블(collection)로 분리 관리
            // - hospital_entry_business_registration_file
            // - hospital_entry_license_file

            $table->string('applicant_name', 50)->comment('신청자 이름');
            $table->string('applicant_position', 50)->nullable()->comment('신청자 직책');
            $table->string('applicant_phone', 30)->nullable()->comment('신청자 전화번호');
            $table->string('applicant_email', 255)->nullable()->comment('신청자 이메일주소');

            $table->string('allow_status', 20)->default('PENDING')->comment('승인상태(PENDING=입점신청, APPROVED=입점승인, REJECTED=입점반려)');

            $table->timestamps();
            $table->softDeletes()->comment('소프트 삭제 시각');

            $table->index(['created_at', 'id'], 'hospital_entries_created_id_idx');
            $table->index(['allow_status', 'created_at'], 'hospital_entries_allow_status_created_idx');
            $table->index('hospital_name', 'hospital_entries_hospital_name_idx');
            $table->index('business_number', 'hospital_entries_business_number_idx');
            $table->index('ceo_name', 'hospital_entries_ceo_name_idx');
            $table->index('applicant_name', 'hospital_entries_applicant_idx');
        });

        DB::statement("ALTER TABLE hospital_entries COMMENT = '병의원 입점신청'");
    }

    public function down(): void
    {
        Schema::dropIfExists('hospital_entries');
    }
};
