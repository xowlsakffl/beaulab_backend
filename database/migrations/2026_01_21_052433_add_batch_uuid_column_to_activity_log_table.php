<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddBatchUuidColumnToActivityLogTable extends Migration
{
    public function up()
    {
        Schema::connection(config('activitylog.database_connection'))
            ->table(config('activitylog.table_name'), function (Blueprint $table) {
                $table->uuid('batch_uuid')
                    ->nullable()
                    ->after('properties')
                    ->comment('여러 Activity Log를 하나의 작업 단위(batch)로 묶기 위한 UUID');
            });
    }

    public function down()
    {
        Schema::connection(config('activitylog.database_connection'))
            ->table(config('activitylog.table_name'), function (Blueprint $table) {
                $table->dropColumn('batch_uuid');
            });
    }
}
