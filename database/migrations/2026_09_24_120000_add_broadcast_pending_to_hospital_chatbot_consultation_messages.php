<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('hospital_chatbot_consultation_messages', function (Blueprint $table) {
            // 저장은 됐는데 브로드캐스트를 놓친 메시지를 찾아 재발행하기 위한 표식이다.
            // 채팅 도메인의 chat_messages.broadcast_pending과 같은 방식을 쓴다.
            $table->boolean('broadcast_pending')
                ->default(false)
                ->after('content')
                ->comment('실시간 발행 대기 여부');

            $table->index('broadcast_pending', 'hccm_broadcast_pending_index');
        });
    }

    public function down(): void
    {
        Schema::table('hospital_chatbot_consultation_messages', function (Blueprint $table) {
            $table->dropIndex('hccm_broadcast_pending_index');
            $table->dropColumn('broadcast_pending');
        });
    }
};
