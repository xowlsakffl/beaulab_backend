<?php

declare(strict_types=1);

namespace Tests\Performance;

use App\Domains\AccountStaff\Models\AccountStaff;
use App\Domains\Common\AdminNote\Queries\AdminNoteListQuery;
use App\Domains\Common\Category\Models\Category;
use App\Domains\Common\Category\Support\CategoryUsageAvailability;
use App\Domains\Hospital\Dto\Staff\HospitalForStaffDto;
use App\Domains\Hospital\Models\Hospital;
use App\Domains\Hospital\Queries\Staff\HospitalListForStaffQuery;
use App\Domains\HospitalChatBot\Queries\Staff\HospitalChatBotStatisticsForStaffQuery;
use App\Domains\HospitalWallet\Queries\Staff\HospitalWalletDashboardForStaffQuery;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\TestCase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

final class BackendQueryPerformanceTest extends TestCase
{
    use RefreshDatabase;

    public function createApplication(): Application
    {
        $app = parent::createApplication();
        if ($app->configurationIsCached() || config('database.connections.mysql.database') !== 'beaulab_testing'
            || ! in_array(config('database.connections.mysql.host'), ['127.0.0.1', 'localhost'], true)) {
            throw new \LogicException('로컬 beaulab_testing DB에서만 실행할 수 있습니다.');
        }

        return $app;
    }

    public function test_query_counts_and_plans_on_synthetic_data(): void
    {
        Bus::fake();
        Http::preventStrayRequests();
        Category::create(['domain' => 'HOSPITAL_DEPARTMENT', 'depth' => 1, 'code' => 'PLASTIC_SURGERY', 'name' => '성형외과', 'full_path' => '성형외과', 'status' => 'ACTIVE']);
        $now = CarbonImmutable::now()->startOfDay();
        foreach (array_chunk(range(1, 1000), 200) as $chunk) {
            DB::table('hospitals')->insert(array_map(fn ($index) => [
                'name' => '성능 검증 의원 '.$index, 'department' => 'PLASTIC_SURGERY', 'allow_status' => 'APPROVED', 'status' => 'ACTIVE',
                'created_at' => $now, 'updated_at' => $now,
            ], $chunk));
        }
        $hospital = Hospital::firstOrFail();
        $list = function (int $perPage): int {
            CategoryUsageAvailability::forgetRequestCache();
            $rows = app(HospitalListForStaffQuery::class)->paginate(['per_page' => $perPage]);
            foreach ($rows as $row) {
                HospitalForStaffDto::fromModel($row)->toArray();
            }

            return $rows->count();
        };
        $small = $this->measure('hospital_list_15_of_1000', fn () => $list(15));
        $large = $this->measure('hospital_list_50_of_1000', fn () => $list(50));
        self::assertSame($small, $large, '페이지 크기에 비례하는 추가 쿼리가 없어야 합니다.');

        $staff = AccountStaff::factory()->create();
        foreach (array_chunk(range(1, 2000), 200) as $chunk) {
            DB::table('admin_notes')->insert(array_map(fn ($index) => [
                'target_type' => Hospital::class, 'target_id' => $hospital->id, 'note' => str_repeat('성능 검증 메모 ', 20),
                'is_internal' => true, 'creator_type' => AccountStaff::class, 'creator_id' => $staff->id,
                'created_at' => $now, 'updated_at' => $now,
            ], $chunk));
        }
        $this->measure('admin_notes_2000', fn () => app(AdminNoteListQuery::class)->getForTarget($hospital, $staff)->count());

        foreach (array_chunk(range(1, 10000), 500) as $chunk) {
            DB::table('hospital_chatbot_consultations')->insert(array_map(function ($index) use ($hospital, $now) {
                $requested = $now->subDays($index % 30)->addMinutes($index % 1400);

                return ['hospital_id' => $hospital->id, 'status' => 'ENDED', 'requested_at' => $requested,
                    'connected_at' => $requested->addMinute(), 'ended_at' => $requested->addMinutes(5),
                    'created_at' => $requested, 'updated_at' => $requested];
            }, $chunk));
        }
        foreach (DB::table('hospital_chatbot_consultations')->orderBy('id')->get(['id', 'connected_at'])->chunk(500) as $chunk) {
            DB::table('hospital_chatbot_consultation_messages')->insert($chunk->map(fn ($row) => [
                'consultation_id' => $row->id, 'sequence' => 1, 'sender_type' => 'AGENT', 'sender_id' => $staff->id,
                'message_type' => 'TEXT', 'content' => '격리된 성능 검증', 'created_at' => $row->connected_at,
            ])->all());
        }
        $this->measure('chatbot_statistics_10000', function () use ($now): int {
            $data = app(HospitalChatBotStatisticsForStaffQuery::class)->overview($now->subDays(29), $now->endOfDay());
            self::assertSame(10000, $data['summary']['requested']);

            return $data['summary']['requested'];
        }, true);

        $this->measure('chatbot_statistics_today_of_10000', function () use ($now): int {
            $data = app(HospitalChatBotStatisticsForStaffQuery::class)->overview($now, $now->endOfDay());
            self::assertSame(333, $data['summary']['requested']);

            return $data['summary']['requested'];
        }, true);

        $this->prepareWalletLedger($now);
        $walletQuery = app(HospitalWalletDashboardForStaffQuery::class);
        $this->measure('wallet_overview_10000_transactions', function () use ($walletQuery, $now): int {
            $data = $walletQuery->overview($now->year);
            $expectedYearUsage = collect([2, 4, 6, 8, 10])
                ->filter(fn ($round) => $now->subDays(11 - $round)->year === $now->year)
                ->count() * 1000000;
            self::assertSame($expectedYearUsage, $data['annual']['used_points']);

            return count($data['monthly']);
        });
        $this->measure('wallet_top_hospitals_10000_transactions', function () use ($walletQuery): int {
            $rows = $walletQuery->topHospitals([]);
            self::assertCount(5, $rows);
            self::assertSame(5000, $rows[0]['used_points']);

            return count($rows);
        });
    }

    private function prepareWalletLedger(CarbonImmutable $now): void
    {
        $hospitalIds = Hospital::query()->orderBy('id')->pluck('id')->all();
        foreach (array_chunk($hospitalIds, 200) as $chunk) {
            DB::table('hospital_wallets')->insert(array_map(fn ($id) => [
                'id' => $id, 'hospital_id' => $id, 'paid_balance' => 0, 'service_balance' => 0,
                'created_at' => $now, 'updated_at' => $now,
            ], $chunk));
        }
        foreach (range(1, 10) as $round) {
            $at = $now->subDays(11 - $round);
            $isGrant = $round % 2 === 1;
            foreach (array_chunk($hospitalIds, 200) as $chunk) {
                $operations = $transactions = $entries = [];
                foreach ($chunk as $hospitalId) {
                    $id = ($round - 1) * 1000 + $hospitalId;
                    $operations[] = [
                        'id' => $id, 'hospital_wallet_id' => $hospitalId, 'type' => $isGrant ? 'SERVICE_GRANT' : 'USAGE',
                        'status' => 'COMPLETED', 'amount' => 1000, 'idempotency_key' => 'performance:'.$id,
                        'requester_kind' => 'SYSTEM', 'processed_at' => $at, 'created_at' => $at, 'updated_at' => $at,
                    ];
                    $transactions[] = [
                        'id' => $id, 'hospital_wallet_operation_id' => $id, 'hospital_wallet_id' => $hospitalId, 'amount' => 1000,
                        'paid_balance_before' => 0, 'paid_balance_after' => 0,
                        'reserved_paid_balance_before' => 0, 'reserved_paid_balance_after' => 0,
                        'service_balance_before' => $isGrant ? 0 : 1000, 'service_balance_after' => $isGrant ? 1000 : 0,
                        'created_at' => $at, 'updated_at' => $at,
                    ];
                    $entries[] = [
                        'hospital_wallet_transaction_id' => $id, 'balance_type' => 'SERVICE',
                        'direction' => $isGrant ? 'CREDIT' : 'DEBIT', 'amount' => 1000, 'created_at' => $at, 'updated_at' => $at,
                    ];
                }
                DB::table('hospital_wallet_operations')->insert($operations);
                DB::table('hospital_wallet_transactions')->insert($transactions);
                DB::table('hospital_wallet_transaction_entries')->insert($entries);
            }
        }
    }

    private function measure(string $name, callable $operation, bool $explain = false): int
    {
        DB::flushQueryLog();
        DB::enableQueryLog();
        memory_reset_peak_usage();
        $memory = memory_get_usage();
        $start = hrtime(true);
        $rows = $operation();
        $elapsed = (hrtime(true) - $start) / 1000000;
        $queries = DB::getQueryLog();
        DB::disableQueryLog();
        fwrite(STDOUT, "\n".json_encode(['scenario' => $name, 'rows' => $rows, 'queries' => count($queries),
            'wall_ms' => round($elapsed, 2), 'sql_ms' => round(array_sum(array_column($queries, 'time')), 2),
            'peak_delta_mb' => round((memory_get_peak_usage() - $memory) / 1048576, 2)], JSON_UNESCAPED_UNICODE)."\n");
        if ($explain) {
            foreach ($queries as $query) {
                if (str_contains($query['query'], 'MIN(m.created_at)')) {
                    fwrite(STDOUT, json_encode(DB::select('EXPLAIN '.$query['query'], $query['bindings']), JSON_UNESCAPED_UNICODE)."\n");
                }
            }
        }

        return count($queries);
    }
}
