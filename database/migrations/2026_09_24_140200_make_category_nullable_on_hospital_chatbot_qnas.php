<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // 분류의 근거가 folder_id로 넘어갔다. category는 기존 조회·필터를 위해 남기는
        // 사본이므로 필수일 이유가 없다. doctrine/dbal 없이 바꾸기 위해 원시 SQL을 쓴다.
        DB::statement("ALTER TABLE hospital_chatbot_qnas MODIFY category VARCHAR(100) NULL COMMENT 'Q&A 분류. folder_id가 정본이고 이 값은 사본이다'");
    }

    public function down(): void
    {
        DB::statement("UPDATE hospital_chatbot_qnas SET category = '기타' WHERE category IS NULL");
        DB::statement("ALTER TABLE hospital_chatbot_qnas MODIFY category VARCHAR(100) NOT NULL COMMENT 'Q&A 분류'");
    }
};
