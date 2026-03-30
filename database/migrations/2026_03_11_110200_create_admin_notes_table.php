<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('admin_notes', function (Blueprint $table) {
            $table->id()->comment('관리자 메모 ID');

            $table->string('target_type', 255)->comment('대상 타입(post, comment, user 등)');
            $table->unsignedBigInteger('target_id')->comment('대상 ID');

            $table->text('note')->comment('관리자 메모 내용');
            $table->boolean('is_internal')->default(true)->comment('내부 메모 여부');
            $table->string('creator_type', 255)->nullable()->comment('메모 작성자 actor 타입');
            $table->unsignedBigInteger('creator_id')->nullable()->comment('메모 작성자 actor ID');

            $table->timestamps();
            $table->softDeletes()->comment('소프트 삭제 시각');

            $table->index(['target_type', 'target_id'], 'admin_notes_target_idx');
            $table->index(['creator_type', 'creator_id'], 'admin_notes_creator_actor_idx');
        });

        DB::statement("ALTER TABLE admin_notes COMMENT = '여러 대상에 연결 가능한 관리자 메모'");
    }

    public function down(): void
    {
        Schema::dropIfExists('admin_notes');
    }
};
