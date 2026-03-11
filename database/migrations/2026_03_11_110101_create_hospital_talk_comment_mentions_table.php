<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hospital_talk_comment_mentions', function (Blueprint $table) {
            $table->id()->comment('토크 댓글 멘션 ID');

            $table->foreignId('hospital_talk_comment_id')
                ->comment('토크 댓글 ID')
                ->constrained('hospital_talk_comments')
                ->cascadeOnDelete();

            $table->foreignId('mentioned_user_id')
                ->comment('멘션 대상 사용자 account_users ID')
                ->constrained('account_users')
                ->cascadeOnDelete();

            $table->foreignId('mentioned_by_user_id')
                ->nullable()
                ->comment('멘션 작성자 account_users ID')
                ->constrained('account_users')
                ->nullOnDelete();

            $table->string('mention_text', 120)->nullable()->comment('멘션 표시 텍스트(@닉네임)');
            $table->unsignedInteger('start_offset')->nullable()->comment('본문 기준 멘션 시작 위치');
            $table->unsignedInteger('end_offset')->nullable()->comment('본문 기준 멘션 끝 위치');

            $table->timestamps();

            $table->unique('hospital_talk_comment_id', 'talk_comment_mentions_comment_unique');
            $table->index(['mentioned_user_id', 'created_at'], 'talk_comment_mentions_user_created_idx');
            $table->index(['mentioned_by_user_id', 'created_at'], 'talk_comment_mentions_by_user_created_idx');
        });

        DB::statement("ALTER TABLE hospital_talk_comment_mentions COMMENT = '토크 댓글 멘션 매핑'");
    }

    public function down(): void
    {
        Schema::dropIfExists('hospital_talk_comment_mentions');
    }
};
