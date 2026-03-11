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

            $table->foreignId('created_by_staff_id')
                ->nullable()
                ->comment('직원(staff) ID')
                ->constrained('account_staffs')
                ->nullOnDelete();

            $table->timestamps();
            $table->softDeletes()->comment('소프트 삭제 시각');

            $table->index(['target_type', 'target_id'], 'admin_notes_target_idx');
            $table->index(['created_by_staff_id', 'created_at'], 'admin_notes_creator_created_idx');
        });

        DB::statement("ALTER TABLE admin_notes COMMENT = '여러 대상에 연결 가능한 관리자 메모'");
    }

    public function down(): void
    {
        Schema::dropIfExists('admin_notes');
    }
};
