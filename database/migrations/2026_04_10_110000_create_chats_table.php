<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('chats', function (Blueprint $table) {
            $table->id()->comment('채팅방 ID');

            $table->string('status', 20)
                ->default('ACTIVE')
                ->comment('운영 상태(ACTIVE, SUSPENDED, CLOSED)');

            $table->string('match_key', 191)
                ->comment('정렬된 두 사용자 ID 기반 1:1 채팅 매칭 키');

            $table->foreignId('created_by_user_id')
                ->nullable()
                ->comment('생성 사용자 account_users ID')
                ->constrained('account_users')
                ->nullOnDelete();

            $table->unsignedBigInteger('last_message_id')
                ->nullable()
                ->comment('마지막 메시지 ID(역정규화)');

            $table->timestamp('last_message_at')
                ->nullable()
                ->comment('마지막 메시지 시각');

            $table->json('metadata')
                ->nullable()
                ->comment('채팅방 메타데이터');

            $table->timestamp('closed_at')
                ->nullable()
                ->comment('채팅 종료 시각');

            $table->timestamps();
            $table->softDeletes()->comment('소프트 삭제 시각');

            $table->unique('match_key', 'chats_match_key_unique');
            $table->index(['status', 'last_message_at'], 'chats_status_last_msg_idx');
            $table->index('created_by_user_id', 'chats_created_by_user_idx');
        });

        DB::statement("ALTER TABLE chats COMMENT = '유저 1:1 채팅방 헤더'");
    }

    public function down(): void
    {
        Schema::dropIfExists('chats');
    }
};
