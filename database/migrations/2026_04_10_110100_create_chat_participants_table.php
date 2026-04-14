<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('chat_participants', function (Blueprint $table) {
            $table->id()->comment('참여자 행 ID');

            $table->foreignId('chat_id')
                ->comment('채팅방 ID')
                ->constrained('chats')
                ->cascadeOnDelete();

            $table->foreignId('account_user_id')
                ->comment('참여 사용자 account_users ID')
                ->constrained('account_users')
                ->cascadeOnDelete();

            $table->unsignedBigInteger('last_read_message_id')
                ->nullable()
                ->comment('마지막 읽은 메시지 ID');

            $table->timestamp('last_read_at')
                ->nullable()
                ->comment('마지막 읽음 시각');

            $table->unsignedBigInteger('deleted_until_message_id')
                ->nullable()
                ->comment('사용자별 채팅 삭제 기준 메시지 ID. 이 ID 이하 메시지는 해당 사용자에게 숨김');

            $table->timestamp('deleted_at')
                ->nullable()
                ->comment('사용자별 채팅 삭제 시각. 참여자 행 삭제가 아니라 내 목록/이전 내용 삭제 기준');

            $table->boolean('notifications_enabled')
                ->default(true)
                ->comment('이 채팅방 알림 수신 여부');

            $table->timestamps();

            $table->unique(['chat_id', 'account_user_id'], 'chat_participants_unique');
            $table->index('account_user_id', 'chat_participants_user_idx');
        });

        DB::statement("ALTER TABLE chat_participants COMMENT = '유저 1:1 채팅 참여자 및 읽음 상태'");
    }

    public function down(): void
    {
        Schema::dropIfExists('chat_participants');
    }
};
