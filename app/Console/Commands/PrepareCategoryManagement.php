<?php

namespace App\Console\Commands;

use App\Domains\Common\Category\Support\CategoryUsageAvailability;
use Database\Seeders\Support\CategoryDefaults;
use Illuminate\Console\Command;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

final class PrepareCategoryManagement extends Command
{
    protected $signature = 'category:prepare-management {--apply : 기존 데이터와 ID를 보존하며 업무별 설정을 준비}';

    protected $description = '분과 카테고리와 병의원·의료진의 독립 사용처 설정을 준비합니다';

    public function handle(): int
    {
        if (Schema::hasTable('category_domain_mappings') && DB::table('category_domain_mappings')->exists()) {
            $this->error('업무별 독립 분류로 전환되어 이전 사용처 준비 명령은 실행할 수 없습니다.');

            return self::FAILURE;
        }
        $this->line('대상: '.config('database.connections.mysql.host').' / '.DB::getDatabaseName());
        if (! $this->option('apply') || DB::getDriverName() !== 'mysql') {
            $this->warn('MySQL 접속 대상과 백업을 확인한 뒤 --apply로 실행하세요.');

            return self::FAILURE;
        }
        if ((int) DB::selectOne("SELECT GET_LOCK('category-management-upgrade', 0) AS acquired")->acquired !== 1) {
            $this->error('다른 카테고리 전환 작업이 실행 중입니다.');

            return self::FAILURE;
        }
        try {
            $backup = storage_path('app/private/category-management/'.now()->format('Ymd-His-u').'.json');
            \Illuminate\Support\Facades\File::ensureDirectoryExists(dirname($backup), 0700);
            \Illuminate\Support\Facades\File::put($backup, json_encode([
                'database' => DB::getDatabaseName(),
                'categories' => DB::table('categories')->orderBy('id')->get(),
                'category_usages' => DB::table('category_usages')->orderBy('id')->get(),
                'category_assignments' => DB::table('category_assignments')->orderBy('id')->get(),
                'hospital_departments' => DB::table('hospitals')->orderBy('id')->get(['id', 'department']),
            ], JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
            $this->line('전환 전 백업: '.$backup);
            if (! Schema::hasColumn('category_usages', 'is_menu_visible')) {
                Schema::table('category_usages', fn (Blueprint $table) => $table->boolean('is_menu_visible')->default(true));
            }
            if (! Schema::hasColumn('category_usages', 'display_name')) {
                Schema::table('category_usages', fn (Blueprint $table) => $table->string('display_name', 120)->nullable());
            }
            DB::transaction(function (): void {
                CategoryDefaults::seedHospitalDepartments();
                $source = DB::table('category_usages')->where('usage', 'HOSPITAL_DOCTOR_SUBJECT')->orderBy('id')->lockForUpdate()->get();
                foreach (['HOSPITAL_SUBJECT', 'DOCTOR_SUBJECT'] as $usage) {
                    foreach ($source as $row) {
                        DB::table('category_usages')->insertOrIgnore([
                            'usage' => $usage, 'category_id' => $row->category_id,
                            'sort_order' => $row->sort_order, 'status' => $row->status,
                            'is_menu_visible' => $row->is_menu_visible, 'display_name' => $row->display_name,
                            'created_at' => now(), 'updated_at' => now(),
                        ]);
                    }
                }
            });
            CategoryUsageAvailability::forgetRequestCache();
            $this->info('분과와 업무별 설정을 준비했습니다. 기존 연결과 분과 값은 변경하지 않았습니다.');

            return self::SUCCESS;
        } finally {
            DB::selectOne("SELECT RELEASE_LOCK('category-management-upgrade')");
        }
    }
}
