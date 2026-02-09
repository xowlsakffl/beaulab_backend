<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('account_partners', function (Blueprint $table) {
            $table->foreignId('hospital_id')
                ->nullable()
                ->after('partner_type')
                ->comment('소속 병원 ID(파트너 타입이 HOSPITAL일 때)')
                ->constrained('hospitals')
                ->nullOnDelete();

            $table->foreignId('beauty_id')
                ->nullable()
                ->after('hospital_id')
                ->comment('소속 뷰티 ID(파트너 타입이 BEAUTY일 때)')
                ->constrained('beauties')
                ->nullOnDelete();

            $table->index('hospital_id');
            $table->index('beauty_id');
        });
    }

    public function down(): void
    {
        Schema::table('account_partners', function (Blueprint $table) {
            $table->dropForeign(['hospital_id']);
            $table->dropForeign(['beauty_id']);

            $table->dropColumn(['hospital_id', 'beauty_id']);
        });
    }
};
