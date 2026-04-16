<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('talk_saves', function (Blueprint $table) {
            $table->id()->comment('토크 저장 ID');

            $table->foreignId('talk_id')
                ->comment('토크 게시글 ID')
                ->constrained('talks')
                ->cascadeOnDelete();

            $table->foreignId('account_user_id')
                ->comment('저장 사용자 ID')
                ->constrained('account_users')
                ->cascadeOnDelete();

            $table->timestamps();

            $table->unique(['talk_id', 'account_user_id'], 'talk_saves_talk_user_unique');
            $table->index(['account_user_id', 'created_at'], 'talk_saves_user_created_idx');
        });

        DB::statement("ALTER TABLE talk_saves COMMENT = '토크 게시글 사용자 저장'");
    }

    public function down(): void
    {
        Schema::dropIfExists('talk_saves');
    }
};
