<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class CreateActivityLogTable extends Migration
{
    public function up()
    {
        Schema::connection(config('activitylog.database_connection'))
            ->create(config('activitylog.table_name'), function (Blueprint $table) {
                $table->bigIncrements('id')->comment('액티비티 로그 ID');

                $table->string('log_name')
                    ->nullable()
                    ->comment('로그 채널/분류 이름');

                $table->text('description')
                    ->comment('행위에 대한 설명');

                $table->nullableMorphs('subject', 'subject');

                $table->nullableMorphs('causer', 'causer');

                $table->json('properties')
                    ->nullable()
                    ->comment('추가 메타 데이터(JSON)');

                $table->timestamps();

                $table->index('log_name');
            });

        DB::statement(
            "ALTER TABLE " . config('activitylog.table_name') . " COMMENT = '[시스템]시스템/관리자/사용자 액티비티 로그 테이블'"
        );
    }

    public function down()
    {
        Schema::connection(config('activitylog.database_connection'))
            ->dropIfExists(config('activitylog.table_name'));
    }
}
