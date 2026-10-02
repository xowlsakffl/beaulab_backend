<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hospital_chatbot_message_settings', function (Blueprint $table) {
            $table->unsignedTinyInteger('id')->primary()->comment('전체 공통 설정 고정 ID 1');
            $table->text('welcome_message')->comment('챗봇 첫 안내');
            $table->text('connection_message')->comment('상담 연결 인사 템플릿');
            $table->json('macros')->comment('상담원 공통 매크로: shortcut, content 목록');
            $table->foreignId('updated_by_staff_id')->nullable()->constrained('account_staffs')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hospital_chatbot_message_settings');
    }
};
