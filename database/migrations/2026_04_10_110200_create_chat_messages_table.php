<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('chat_messages', function (Blueprint $table) {
            $table->id()->comment('메시지 ID');

            $table->foreignId('chat_id')
                ->comment('채팅방 ID')
                ->constrained('chats')
                ->cascadeOnDelete();

            $table->foreignId('sender_user_id')
                ->comment('발신 사용자 account_users ID')
                ->constrained('account_users');

            $table->string('client_message_id', 64)
                ->nullable()
                ->comment('클라이언트 생성 멱등 키');

            $table->string('message_type', 20)
                ->default('TEXT')
                ->comment('메시지 유형(TEXT, IMAGE, FILE)');

            $table->longText('body')
                ->nullable()
                ->comment('메시지 본문. 첨부만 있는 경우 null 가능');

            $table->foreignId('reply_to_message_id')
                ->nullable()
                ->comment('답장 대상 메시지 ID')
                ->constrained('chat_messages')
                ->nullOnDelete();

            $table->json('metadata')
                ->nullable()
                ->comment('미리보기, 클라이언트 정보 등 메시지 메타데이터');

            $table->timestamp('edited_at')
                ->nullable()
                ->comment('수정 시각');

            $table->timestamps();
            $table->softDeletes()->comment('소프트 삭제 시각');

            $table->unique(
                ['chat_id', 'sender_user_id', 'client_message_id'],
                'chat_messages_sender_client_unique'
            );
            $table->index(['chat_id', 'id'], 'chat_messages_chat_id_idx');
            $table->index(['sender_user_id', 'created_at'], 'chat_messages_sender_created_idx');
            $table->index(['message_type', 'created_at'], 'chat_messages_type_created_idx');
        });

        Schema::table('chats', function (Blueprint $table) {
            $table->foreign('last_message_id', 'chats_last_message_fk')
                ->references('id')
                ->on('chat_messages')
                ->nullOnDelete();
        });

        Schema::table('chat_participants', function (Blueprint $table) {
            $table->foreign('last_read_message_id', 'chat_participants_last_read_message_fk')
                ->references('id')
                ->on('chat_messages')
                ->nullOnDelete();
        });

        DB::statement("ALTER TABLE chat_messages COMMENT = '유저 1:1 채팅 영속 메시지'");
    }

    public function down(): void
    {
        Schema::table('chat_participants', function (Blueprint $table) {
            $table->dropForeign('chat_participants_last_read_message_fk');
        });

        Schema::table('chats', function (Blueprint $table) {
            $table->dropForeign('chats_last_message_fk');
        });

        Schema::dropIfExists('chat_messages');
    }
};
