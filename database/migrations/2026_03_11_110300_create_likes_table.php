<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('likes', function (Blueprint $table) {
            $table->id()->comment('공용 좋아요 ID');

            $table->string('likeable_type', 255)->comment('좋아요 대상 타입(모델 클래스)');
            $table->unsignedBigInteger('likeable_id')->comment('좋아요 대상 ID');

            $table->foreignId('account_user_id')
                ->comment('좋아요 사용자 ID')
                ->constrained('account_users')
                ->cascadeOnDelete();

            $table->timestamps();

            $table->unique(
                ['likeable_type', 'likeable_id', 'account_user_id'],
                'likes_likeable_user_unique'
            );
            $table->index(['likeable_type', 'likeable_id'], 'likes_likeable_idx');
            $table->index(['account_user_id', 'created_at'], 'likes_user_created_idx');
        });

        DB::statement("ALTER TABLE likes COMMENT = '서비스 공용 좋아요'");
    }

    public function down(): void
    {
        Schema::dropIfExists('likes');
    }
};
