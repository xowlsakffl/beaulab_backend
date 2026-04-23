<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('talk_polls', function (Blueprint $table) {
            $table->id()->comment('토크 투표 ID');

            $table->foreignId('talk_id')
                ->comment('토크 ID')
                ->constrained('talks')
                ->cascadeOnDelete();

            $table->boolean('allow_multiple')->default(false)->comment('복수 선택 허용 여부');

            $table->timestamps();

            $table->unique('talk_id', 'talk_polls_talk_unique');
        });

        DB::statement("ALTER TABLE talk_polls COMMENT = '토크 투표'");

        Schema::create('talk_poll_options', function (Blueprint $table) {
            $table->id()->comment('토크 투표 항목 ID');

            $table->foreignId('talk_poll_id')
                ->comment('토크 투표 ID')
                ->constrained('talk_polls')
                ->cascadeOnDelete();

            $table->string('content', 100)->comment('투표 항목 내용');
            $table->unsignedTinyInteger('sort_order')->default(0)->comment('투표 항목 정렬 순서');
            $table->unsignedInteger('vote_count')->default(0)->comment('투표 수');

            $table->timestamps();

            $table->unique(['talk_poll_id', 'content'], 'talk_poll_options_poll_content_unique');
            $table->unique(['talk_poll_id', 'sort_order'], 'talk_poll_options_poll_sort_unique');
        });

        DB::statement("ALTER TABLE talk_poll_options COMMENT = '토크 투표 항목'");

        Schema::create('talk_poll_votes', function (Blueprint $table) {
            $table->id()->comment('토크 투표 선택 ID');

            $table->foreignId('talk_poll_id')
                ->comment('토크 투표 ID')
                ->constrained('talk_polls')
                ->cascadeOnDelete();

            $table->foreignId('talk_poll_option_id')
                ->comment('토크 투표 항목 ID')
                ->constrained('talk_poll_options')
                ->cascadeOnDelete();

            $table->foreignId('user_id')
                ->comment('투표자 account_users ID')
                ->constrained('account_users')
                ->cascadeOnDelete();

            $table->timestamps();

            $table->unique(['talk_poll_id', 'user_id', 'talk_poll_option_id'], 'talk_poll_votes_poll_user_option_unique');
            $table->index(['user_id', 'created_at'], 'talk_poll_votes_user_created_idx');
        });

        DB::statement("ALTER TABLE talk_poll_votes COMMENT = '토크 투표 선택'");
    }

    public function down(): void
    {
        Schema::dropIfExists('talk_poll_votes');
        Schema::dropIfExists('talk_poll_options');
        Schema::dropIfExists('talk_polls');
    }
};
