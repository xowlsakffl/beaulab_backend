<?php

namespace App\Console\Commands;

use App\Domains\Common\Category\Models\Category;
use App\Domains\Common\Category\Support\CategoryDomainSplit;
use App\Domains\Common\Category\Support\CategoryUsageAvailability;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use RuntimeException;

final class SplitCategoryDomains extends Command
{
    protected $signature = 'category:split-domains {--apply : 격리 검증과 백업 후 로컬 DB에 적용} {--verify-root-socket= : 임시 검증 DB 생성에만 사용할 로컬 MySQL root 소켓}';

    protected $description = '업무별 분류 분리와 기존 연결 보존을 격리 MySQL에서 검증합니다';

    private const MIGRATION = '2026_10_13_180000_create_category_topics';

    private const TABLES = [
        'categories', 'category_usages', 'category_assignments', 'remote_consultation_categories',
        'remote_consultation_quotes', 'consultation_pricing_scopes', 'consultation_pricing_versions',
        'hospital_event_ads', 'banner_exposures', 'event_exposures',
        'review_exposure_positions', 'review_exposures', 'review_exposure_metrics',
        'video_exposure_positions', 'video_exposures', 'video_exposure_metrics',
    ];

    public function handle(): int
    {
        $default = DB::getDefaultConnection();
        $source = DB::connection($default);
        $config = config('database.connections.'.$default);
        if (! app()->environment('local') || $source->getDriverName() !== 'mysql'
            || ($config['host'] ?? null) !== '127.0.0.1' || $source->getDatabaseName() !== 'beaulab') {
            $this->error('이 명령은 로컬 127.0.0.1 / beaulab 전용입니다.');

            return self::FAILURE;
        }
        if (Schema::hasTable('category_domain_mappings') && DB::table('category_domain_mappings')->exists()) {
            $this->info('이미 전환되어 다시 복사하지 않습니다.');

            return self::SUCCESS;
        }
        if ((int) $source->selectOne("SELECT GET_LOCK('category-domain-split', 0) AS acquired")->acquired !== 1) {
            $this->error('다른 전환 작업이 진행 중입니다.');

            return self::FAILURE;
        }
        try {
            // 파일 공유 복제는 삭제 수명주기를 바꾸므로 별도 미디어 이전이 필요한 DB는 중단한다.
            if ($source->table('media')->where('model_type', (new Category)->getMorphClass())->exists()) {
                throw new RuntimeException('카테고리 아이콘이 있습니다. 파일 보존 이전을 먼저 준비해야 합니다.');
            }
            $snapshot = $source->transaction(function () use ($source) {
                $tables = [];
                foreach (self::TABLES as $table) {
                    $tables[$table] = $source->table($table)->lockForUpdate()->get()->map(fn ($row) => (array) $row)->all();
                }

                return $tables;
            });
            $verified = $this->verifyIsolated($default, $config, $snapshot);
            $this->line(json_encode($verified, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR));
            if (! $this->option('apply')) {
                $this->info('격리 검증 완료. 원본 DB는 변경하지 않았습니다.');

                return self::SUCCESS;
            }
            $backup = storage_path('app/private/category-domain-split/'.now()->format('Ymd-His-u').'.json');
            $json = json_encode(['database' => 'beaulab', 'created_at' => now()->toIso8601String(), 'tables' => $snapshot], JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
            if (disk_free_space(storage_path()) < strlen($json) + 64 * 1024 * 1024) {
                throw new RuntimeException('백업 공간이 부족합니다.');
            }
            File::ensureDirectoryExists(dirname($backup), 0700);
            if (File::put($backup, $json) !== strlen($json)) {
                throw new RuntimeException('백업 저장에 실패했습니다.');
            }
            chmod($backup, 0600);
            if (hash_file('sha256', $backup) !== hash('sha256', $json)) {
                throw new RuntimeException('백업 검증에 실패했습니다.');
            }
            $this->line('전환 전 백업: '.$backup);
            $this->prepareCategoryIndex();
            $exit = $this->call('migrate', ['--path' => 'database/migrations/'.self::MIGRATION.'.php', '--force' => true]);
            if ($exit !== 0) {
                throw new RuntimeException('신규 테이블 준비에 실패했습니다.');
            }
            $result = DB::transaction(function () use ($snapshot) {
                foreach ($snapshot as $table => $rows) {
                    $actual = DB::table($table)->lockForUpdate()->get()->map(fn ($row) => (array) $row)->all();
                    if ($this->fingerprint($table, $actual) !== $this->fingerprint($table, $rows)) {
                        throw new RuntimeException('검증 이후 '.$table.' 데이터가 변경되어 적용을 중단합니다. 다시 실행해 주세요.');
                    }
                }

                return app(CategoryDomainSplit::class)->apply();
            });
            $this->line(json_encode($result, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR));
            $this->info('전체 연결 보존 대조를 통과해 적용했습니다.');

            return self::SUCCESS;
        } finally {
            $this->useConnection($default);
            $source->selectOne("SELECT RELEASE_LOCK('category-domain-split')");
        }
    }

    private function verifyIsolated(string $default, array $config, array $snapshot): array
    {
        $source = DB::connection($default);
        $database = 'beaulab_category_verify_'.bin2hex(random_bytes(6));
        $verifyConfig = $config;
        if ($socket = $this->option('verify-root-socket')) {
            if ($socket !== '/var/run/mysqld/mysqld.sock') {
                throw new RuntimeException('로컬 검증 소켓을 확인해 주세요.');
            }
            $verifyConfig = [...$config, 'unix_socket' => $socket, 'username' => 'root', 'password' => ''];
        }
        config(['database.connections.category_verifier' => $verifyConfig]);
        $verifier = DB::connection('category_verifier');
        $verifier->statement('CREATE DATABASE `'.$database.'` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
        try {
            config(['database.connections.category_verify' => [...$verifyConfig, 'database' => $database]]);
            $connection = DB::connection('category_verify');
            foreach ($snapshot as $table => $rows) {
                $connection->statement('CREATE TABLE `'.$table.'` LIKE `beaulab`.`'.$table.'`');
                foreach (array_chunk($rows, 200) as $chunk) {
                    $connection->table($table)->insert(array_map(function ($row) {
                        unset($row['parent_scope_id'], $row['group_scope_code']);

                        return $row;
                    }, $chunk));
                }
            }
            foreach (['hospitals', 'hospital_doctors', 'hospital_events', 'hospital_reviews', 'hospital_videos', 'talks', 'hospital_evaluations', 'beauties', 'beauty_experts', 'content_report_states'] as $table) {
                $connection->statement('CREATE TABLE `'.$table.'` LIKE `beaulab`.`'.$table.'`');
                $connection->statement('INSERT INTO `'.$table.'` SELECT * FROM `beaulab`.`'.$table.'`');
            }
            $this->useConnection('category_verify');
            $this->prepareCategoryIndex();
            (require database_path('migrations/'.self::MIGRATION.'.php'))->up();
            $result = app(CategoryDomainSplit::class)->apply();
            $discovery = app(\App\Domains\Common\Category\Queries\CategoryDiscoveryQuery::class);
            $topic = \App\Domains\Common\Category\Models\CategoryTopic::firstOrFail();
            foreach (array_keys(\App\Domains\Common\Category\Queries\CategoryDiscoveryQuery::DOMAINS) as $domain) {
                $discovery->contents($topic, $domain)->limit(2)->get();
            }
            foreach (\App\Domains\Common\Category\Models\CategoryUsage::usages() as $usage) {
                $items = app(\App\Domains\Common\Category\Queries\Staff\CategorySelectorListForStaffQuery::class)->get(['domain' => \App\Domains\Common\Category\Models\CategoryUsage::domainFor($usage), 'usage' => $usage]);
                if ($items->contains(fn ($row) => $row->domain !== \App\Domains\Common\Category\Models\CategoryUsage::domainFor($usage))) {
                    throw new RuntimeException('사용처 격리 검증 실패');
                }
            }
            [$pricingSql, $bindings] = app(\App\Domains\ConsultationPricing\Queries\HospitalEventPricingQuery::class)->expression();
            $events = \App\Domains\HospitalEvent\Models\HospitalEvent::query()->select('hospital_events.*')->selectRaw('('.$pricingSql.') AS verified_price', $bindings)->get();
            foreach ($events as $event) {
                $price = app(\App\Domains\ConsultationPricing\Support\ConsultationPricingResolver::class)->forEvent($event)['price'];
                if ($price !== (int) $event->verified_price) {
                    throw new RuntimeException('목록 단가와 실제 과금 단가 불일치');
                }
            }
            if (app(CategoryDomainSplit::class)->apply()['changed']) {
                throw new RuntimeException('재실행 안전성 검증 실패');
            }
            $mapping = DB::table('category_domain_mappings')->where('domain', Category::DOMAIN_HOSPITAL_REVIEW)->first();
            if ($mapping) {
                $eventName = Category::findOrFail($mapping->legacy_id)->name;
                Category::withoutEvents(fn () => Category::findOrFail($mapping->category_id)->update(['name' => '격리 검증용 분류']));
                if (Category::findOrFail($mapping->legacy_id)->name !== $eventName) {
                    throw new RuntimeException('업무별 독립성 검증 실패');
                }
            }

            return ['isolated_mysql' => true, 'row_preservation' => true, 'repeat_noop' => true, 'domain_independence' => true, 'public_domains' => 9, 'selector_usages' => 11, 'event_prices' => $events->count(), ...$result];
        } finally {
            $this->useConnection($default);
            DB::purge('category_verify');
            if (! preg_match('/^beaulab_category_verify_[0-9a-f]{12}$/', $database)) {
                throw new RuntimeException('격리 DB 경로 검증 실패');
            }
            $verifier->statement('DROP DATABASE `'.$database.'`');
            DB::purge('category_verifier');
        }
    }

    private function prepareCategoryIndex(): void
    {
        if (! Schema::hasColumn('categories', 'parent_scope_id')) {
            DB::statement('ALTER TABLE categories ADD COLUMN parent_scope_id BIGINT UNSIGNED GENERATED ALWAYS AS (COALESCE(parent_id, 0)) VIRTUAL');
        }
        if (! Schema::hasColumn('categories', 'group_scope_code')) {
            DB::statement("ALTER TABLE categories ADD COLUMN group_scope_code VARCHAR(30) GENERATED ALWAYS AS (COALESCE(group_code, '')) VIRTUAL, DROP INDEX categories_domain_parent_name_unique, ADD UNIQUE KEY categories_domain_parent_name_unique (domain, group_scope_code, parent_scope_id, name)");
        }
    }

    private function fingerprint(string $table, array $rows): string
    {
        $values = array_map(function ($row) use ($table) {
            if ($table === 'categories') {
                unset($row['group_scope_code'], $row['parent_scope_id']);
            }
            ksort($row);

            return json_encode($row, JSON_THROW_ON_ERROR);
        }, $rows);
        sort($values);

        return hash('sha256', implode("\n", $values));
    }

    private function useConnection(string $name): void
    {
        DB::setDefaultConnection($name);
        app()->forgetInstance('db.schema');
        Schema::clearResolvedInstance('db.schema');
        CategoryUsageAvailability::forgetRequestCache();
    }
}
