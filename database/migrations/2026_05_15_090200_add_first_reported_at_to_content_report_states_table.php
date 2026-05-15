<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('content_report_states', 'first_reported_at')) {
            Schema::table('content_report_states', function (Blueprint $table): void {
                $table->timestamp('first_reported_at')
                    ->nullable()
                    ->after('recent_hour_report_count')
                    ->comment('최초 신고 일시');
            });
        }

        DB::table('content_report_states')
            ->whereNull('first_reported_at')
            ->orderBy('id')
            ->select(['id', 'target_type', 'target_id'])
            ->chunkById(100, function ($states): void {
                foreach ($states as $state) {
                    $firstReportedAt = DB::table('content_reports')
                        ->where('target_type', $state->target_type)
                        ->where('target_id', $state->target_id)
                        ->min('created_at');

                    if ($firstReportedAt === null) {
                        continue;
                    }

                    DB::table('content_report_states')
                        ->where('id', $state->id)
                        ->update(['first_reported_at' => $firstReportedAt]);
                }
            });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('content_report_states', 'first_reported_at')) {
            return;
        }

        Schema::table('content_report_states', function (Blueprint $table): void {
            $table->dropColumn('first_reported_at');
        });
    }
};
