<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const INDEX_NAME = 'content_reports_reporter_target_unique';

    private const REPORTER_INDEX_NAME = 'content_reports_reporter_user_idx';

    public function up(): void
    {
        if (! Schema::hasTable('content_reports') || ! $this->indexExists(self::INDEX_NAME)) {
            return;
        }

        if (! $this->indexExists(self::REPORTER_INDEX_NAME)) {
            Schema::table('content_reports', function (Blueprint $table): void {
                $table->index('reporter_user_id', self::REPORTER_INDEX_NAME);
            });
        }

        Schema::table('content_reports', function (Blueprint $table): void {
            $table->dropUnique(self::INDEX_NAME);
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('content_reports') || $this->indexExists(self::INDEX_NAME)) {
            return;
        }

        $hasDuplicates = DB::table('content_reports')
            ->select(['reporter_user_id', 'target_type', 'target_id'])
            ->groupBy(['reporter_user_id', 'target_type', 'target_id'])
            ->havingRaw('COUNT(*) > 1')
            ->exists();

        if ($hasDuplicates) {
            return;
        }

        Schema::table('content_reports', function (Blueprint $table): void {
            $table->unique(['reporter_user_id', 'target_type', 'target_id'], self::INDEX_NAME);
        });

        if ($this->indexExists(self::REPORTER_INDEX_NAME)) {
            Schema::table('content_reports', function (Blueprint $table): void {
                $table->dropIndex(self::REPORTER_INDEX_NAME);
            });
        }
    }

    private function indexExists(string $indexName): bool
    {
        $driver = DB::connection()->getDriverName();

        if ($driver === 'sqlite') {
            return collect(DB::select('PRAGMA index_list(content_reports)'))
                ->contains(static fn (object $index): bool => (string) ($index->name ?? '') === $indexName);
        }

        if ($driver === 'pgsql') {
            return DB::table('pg_indexes')
                ->where('tablename', 'content_reports')
                ->where('indexname', $indexName)
                ->exists();
        }

        return DB::table('information_schema.statistics')
            ->whereRaw('table_schema = DATABASE()')
            ->where('table_name', 'content_reports')
            ->where('index_name', $indexName)
            ->exists();
    }
};
