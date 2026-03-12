<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('jobs', function (Blueprint $table) {
            $table->id()->comment('작업 ID');
            $table->string('queue')->index()->comment('큐 이름');
            $table->longText('payload')->comment('직렬화된 작업 데이터');
            $table->unsignedTinyInteger('attempts')->comment('실행 시도 횟수');
            $table->unsignedInteger('reserved_at')->nullable()->comment('작업 예약 시간');
            $table->unsignedInteger('available_at')->comment('작업 실행 가능 시간');
            $table->unsignedInteger('created_at')->comment('작업 생성 시간');
        });

        DB::statement("ALTER TABLE jobs COMMENT = '[시스템] Laravel 대기 작업 - 사용 X'");

        Schema::create('job_batches', function (Blueprint $table) {
            $table->string('id')->primary()->comment('배치 작업 ID');
            $table->string('name')->comment('배치 작업 이름');
            $table->integer('total_jobs')->comment('전체 작업 수');
            $table->integer('pending_jobs')->comment('대기 중인 작업 수');
            $table->integer('failed_jobs')->comment('실패한 작업 수');
            $table->longText('failed_job_ids')->comment('실패한 작업 ID 목록');
            $table->mediumText('options')->nullable()->comment('배치 옵션');
            $table->integer('cancelled_at')->nullable()->comment('배치 취소 시간');
            $table->integer('created_at')->comment('배치 생성 시간');
            $table->integer('finished_at')->nullable()->comment('배치 완료 시간');
        });

        DB::statement("ALTER TABLE job_batches COMMENT = '[시스템] Laravel 배치 작업 관리'");

        Schema::create('failed_jobs', function (Blueprint $table) {
            $table->id()->comment('실패 작업 ID');
            $table->string('uuid')->unique()->comment('실패 작업 UUID');
            $table->text('connection')->comment('큐 커넥션 정보');
            $table->text('queue')->comment('큐 이름');
            $table->longText('payload')->comment('작업 데이터');
            $table->longText('exception')->comment('예외 메시지');
            $table->timestamp('failed_at')->useCurrent()->comment('실패 시간');
        });

        DB::statement("ALTER TABLE failed_jobs COMMENT = '[시스템] 실패한 큐 작업 로그'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('jobs');
        Schema::dropIfExists('job_batches');
        Schema::dropIfExists('failed_jobs');
    }
};
