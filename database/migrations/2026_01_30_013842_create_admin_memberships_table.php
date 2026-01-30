<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('admin_memberships', function (Blueprint $table) {
            $table->id()->comment('관리자 소속(멤버십) 고유 ID');

            $table->foreignId('admin_id')
                ->constrained('admins')
                ->cascadeOnDelete()
                ->comment('관리자 ID');

            // internal / hospital / beauty / agency
            $table->string('type', 20)->comment('소속 타입(internal, hospital, beauty, agency)');

            // internal = 0 (내부 직원)
            $table->unsignedBigInteger('target_id')
                ->default(0)
                ->comment('소속 대상 ID (internal=0, hospital_id/beauty_id/agency_id)');

            // 소속 내 역할
            $table->string('role', 30)
                ->nullable()
                ->comment('소속 내 역할(owner, manager, staff 등)');

            $table->boolean('is_primary')
                ->default(false)
                ->comment('기본 소속 여부');

            $table->timestamps();

            // 인덱스
            $table->index(['admin_id', 'type']);
            $table->index(['type', 'target_id']);

            // 동일 관리자에게 동일 소속 중복 방지
            $table->unique(
                ['admin_id', 'type', 'target_id'],
                'uq_admin_memberships_admin_type_target'
            );
        });

        DB::statement("ALTER TABLE admin_memberships COMMENT = '관리자 소속 멤버십 매핑 테이블'");
    }

    public function down(): void
    {
        Schema::dropIfExists('admin_memberships');
    }
};
