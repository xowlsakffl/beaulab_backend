<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hospital_chatbot_closure_dates', function (Blueprint $table) {
            $table->id()->comment('상담 휴무일 ID');
            $table->date('closure_date')->unique()->comment('휴무 시작일');
            $table->date('end_date')->nullable()->index()->comment('휴무 종료일, null이면 시작일 하루');
            $table->string('reason', 255)->nullable()->comment('공휴일·임시휴무 사유');
            $table->foreignId('updated_by_staff_id')
                ->nullable()
                ->comment('마지막 수정 직원 계정 ID')
                ->constrained('account_staffs')
                ->nullOnDelete();
            $table->timestamps();
        });

        DB::statement("ALTER TABLE hospital_chatbot_closure_dates COMMENT = '병의원 챗봇 공휴일·임시휴무 예외'");
    }

    public function down(): void
    {
        Schema::dropIfExists('hospital_chatbot_closure_dates');
    }
};
