<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class CreateScheduleMonitorTables extends Migration
{
    public function up()
    {
        Schema::create('monitored_scheduled_tasks', function (Blueprint $table) {
            $table->bigIncrements('id')->comment('스케줄 작업 고유 ID');

            $table->string('name')->comment('스케줄 작업 이름');
            $table->string('type')->nullable()->comment('스케줄 작업 타입(분류용)');
            $table->string('cron_expression')->comment('크론 실행 표현식');
            $table->string('timezone')->nullable()->comment('실행 타임존');
            $table->string('ping_url')->nullable()->comment('외부 모니터링 ping URL');

            $table->dateTime('last_started_at')->nullable()->comment('마지막 실행 시작 시각');
            $table->dateTime('last_finished_at')->nullable()->comment('마지막 정상 종료 시각');
            $table->dateTime('last_failed_at')->nullable()->comment('마지막 실패 시각');
            $table->dateTime('last_skipped_at')->nullable()->comment('마지막 스킵 시각');

            $table->dateTime('registered_on_oh_dear_at')->nullable()->comment('Oh Dear 등록 시각');
            $table->dateTime('last_pinged_at')->nullable()->comment('마지막 ping 시각');
            $table->integer('grace_time_in_minutes')->comment('허용 지연 시간(분)');

            $table->timestamps();
        });

        DB::statement("ALTER TABLE monitored_scheduled_tasks COMMENT = '[시스템]스케줄(cron) 작업 모니터링 테이블'");

        Schema::create('monitored_scheduled_task_log_items', function (Blueprint $table) {
            $table->bigIncrements('id')->comment('스케줄 작업 로그 ID');

            $table->unsignedBigInteger('monitored_scheduled_task_id')->comment('스케줄 작업 ID');
            $table
                ->foreign('monitored_scheduled_task_id', 'fk_scheduled_task_id')
                ->references('id')
                ->on('monitored_scheduled_tasks')
                ->cascadeOnDelete();

            $table->string('type')->comment('이벤트 타입(started, finished, failed, skipped)');
            $table->json('meta')->nullable()->comment('이벤트 부가 정보(JSON)');

            $table->timestamps();
        });

        DB::statement("ALTER TABLE monitored_scheduled_task_log_items COMMENT = '[시스템]스케줄 작업 실행 로그 테이블'");
    }

    public function down()
    {
        Schema::dropIfExists('monitored_scheduled_task_log_items');
        Schema::dropIfExists('monitored_scheduled_tasks');
    }
}
