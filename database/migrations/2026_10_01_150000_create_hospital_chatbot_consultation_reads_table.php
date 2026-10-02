<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hospital_chatbot_consultation_reads', function (Blueprint $table) {
            $table->foreignId('consultation_id');
            $table->foreignId('staff_id');
            $table->unsignedBigInteger('last_read_sequence')->default(0)->comment('직원이 마지막으로 확인한 메시지 순번');
            $table->timestamp('updated_at');
            $table->primary(['consultation_id', 'staff_id'], 'hccr_primary');
            $table->foreign('consultation_id', 'hccr_consultation_fk')->references('id')->on('hospital_chatbot_consultations')->cascadeOnDelete();
            $table->foreign('staff_id', 'hccr_staff_fk')->references('id')->on('account_staffs')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hospital_chatbot_consultation_reads');
    }
};
