<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('admins', function (Blueprint $table) {
            $table->foreignId('active_membership_id')
                ->nullable()
                ->after('last_login_at')
                ->comment('현재 활성 관리자 소속(admin_memberships.id)');

            $table->index('active_membership_id');

            $table->foreign('active_membership_id')
                ->references('id')
                ->on('admin_memberships')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('admins', function (Blueprint $table) {
            $table->dropForeign(['active_membership_id']);
            $table->dropIndex(['active_membership_id']);
            $table->dropColumn('active_membership_id');
        });
    }
};
