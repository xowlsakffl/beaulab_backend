<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Common\Exceptions\CustomException;
use App\Domains\AccountUser\Models\AccountUser;
use App\Domains\AccountUser\Queries\Staff\AccountUserStatusUpdateForStaffQuery;
use App\Domains\Common\Category\Models\Category;
use App\Domains\Common\ContentReport\Actions\ContentReportUserWarningAction;
use App\Domains\Hospital\Actions\Staff\HospitalUpdateHistoryRecordAction;
use App\Domains\Hospital\Models\Hospital;
use App\Domains\HospitalEvent\Actions\User\HospitalEventDBCreateForUserAction;
use App\Domains\HospitalEvent\Actions\User\HospitalEventRealModelDBCreateForUserAction;
use App\Domains\HospitalEvent\Models\HospitalEvent;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\TestCase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use PHPUnit\Framework\Attributes\DataProvider;

final class BackendHardeningRegressionTest extends TestCase
{
    use RefreshDatabase;

    public function createApplication(): Application
    {
        $app = parent::createApplication();
        if ($app->configurationIsCached()
            || config('database.connections.mysql.database') !== 'beaulab_testing'
            || ! in_array(config('database.connections.mysql.host'), ['127.0.0.1', 'localhost'], true)) {
            throw new \LogicException('로컬 beaulab_testing DB에서만 실행할 수 있습니다.');
        }

        return $app;
    }

    protected function setUp(): void
    {
        parent::setUp();
        Bus::fake();
        Http::preventStrayRequests();
        Category::create(['domain' => 'HOSPITAL_DEPARTMENT', 'depth' => 1, 'code' => 'PLASTIC_SURGERY',
            'name' => '성형외과', 'full_path' => '성형외과', 'status' => 'ACTIVE']);
    }

    public static function unavailableApplications(): iterable
    {
        foreach ([HospitalEventDBCreateForUserAction::class, HospitalEventRealModelDBCreateForUserAction::class] as $action) {
            foreach (['suspended', 'withdrawn', 'rejected', 'deleted', 'event_private', 'event_deleted'] as $condition) {
                yield class_basename($action).':'.$condition => [$action, $condition];
            }
        }
    }

    #[DataProvider('unavailableApplications')]
    public function test_application_rechecks_current_hospital_and_event(string $action, string $condition): void
    {
        $event = HospitalEvent::factory()->active()->approved()->create(['event_start_at' => now()->subDay(), 'is_event_period_unlimited' => true]);
        $event->load('hospital');
        match ($condition) {
            'suspended' => Hospital::whereKey($event->hospital_id)->update(['status' => Hospital::STATUS_SUSPENDED]),
            'withdrawn' => Hospital::whereKey($event->hospital_id)->update(['status' => Hospital::STATUS_WITHDRAWN]),
            'rejected' => Hospital::whereKey($event->hospital_id)->update(['allow_status' => Hospital::ALLOW_REJECTED]),
            'deleted' => Hospital::whereKey($event->hospital_id)->delete(),
            'event_private' => HospitalEvent::whereKey($event->id)->update(['hospital_status' => HospitalEvent::HOSPITAL_STATUS_PRIVATE]),
            'event_deleted' => HospitalEvent::whereKey($event->id)->delete(),
        };

        $this->expectException(CustomException::class);
        $this->expectExceptionMessage('신청 가능한 이벤트가 아닙니다.');
        app($action)->execute(AccountUser::factory()->create(), $event, []);
    }

    public function test_open_event_accepts_applications_and_preserves_duplicate_policy(): void
    {
        $event = HospitalEvent::factory()->active()->approved()->create(['event_start_at' => now()->subDay(), 'is_event_period_unlimited' => true]);
        $category = Category::create(['domain' => Category::DOMAIN_HOSPITAL_MEDICAL, 'depth' => 1, 'group_code' => 'SURGERY', 'name' => '눈', 'full_path' => '눈', 'status' => 'ACTIVE']);
        $event->categories()->attach($category->id, ['is_primary' => true]);
        $user = AccountUser::factory()->create();
        $payload = ['name' => '신청 테스트', 'phone' => '010-0000-1234', 'contact_method' => 'PHONE', 'preferred_time' => 'ANYTIME'];
        app(HospitalEventDBCreateForUserAction::class)->execute($user, $event, $payload);
        app(HospitalEventDBCreateForUserAction::class)->execute($user, $event, $payload);
        self::assertSame(['NEW', 'DUPLICATE'], DB::table('hospital_event_dbs')->orderBy('id')->pluck('status')->all());
    }

    public static function protectedStatuses(): iterable
    {
        yield 'manual block' => [AccountUser::STATUS_BLOCKED];
        yield 'suspension' => [AccountUser::STATUS_SUSPENDED];
        yield 'withdrawal' => [AccountUser::STATUS_WITHDRAWN];
    }

    #[DataProvider('protectedStatuses')]
    public function test_warning_undo_preserves_manual_restrictions(string $status): void
    {
        $user = AccountUser::factory()->create(['status' => $status, 'warning_count' => 9, 'blocked_by_warning' => false]);
        $action = app(ContentReportUserWarningAction::class);
        $action->execute($user->id, true);
        $action->execute($user->id, false);
        self::assertSame($status, $user->fresh()->status);
        self::assertSame(9, $user->fresh()->warning_count);
    }

    public function test_warning_auto_block_can_be_undone_but_manual_override_cannot(): void
    {
        $user = AccountUser::factory()->create(['status' => 'ACTIVE', 'warning_count' => 9]);
        $action = app(ContentReportUserWarningAction::class);
        $action->execute($user->id, true);
        self::assertTrue($user->fresh()->blocked_by_warning);
        $action->execute($user->id, false);
        self::assertSame('ACTIVE', $user->fresh()->status);
        $action->execute($user->id, true);
        app(AccountUserStatusUpdateForStaffQuery::class)->update($user->fresh(), 'BLOCKED');
        $action->execute($user->id, false);
        self::assertSame('BLOCKED', $user->fresh()->status);
        self::assertFalse($user->fresh()->blocked_by_warning);
    }

    public function test_settlement_account_is_masked_without_losing_change_detection(): void
    {
        $hospital = Hospital::factory()->active()->approved()->create();
        $registration = $hospital->businessRegistration()->create(['business_number' => '000-00-12345', 'company_name' => '검증 의원', 'ceo_name' => '테스트', 'settlement_account_number' => '111122223333']);
        $history = app(HospitalUpdateHistoryRecordAction::class);
        $before = $history->capture($hospital);
        $registration->update(['settlement_account_number' => '999988883333']);
        $history->recordUpdated($hospital, $before);
        $changes = DB::table('operation_history_changes')->where('field_key', 'settlement_account')->get();
        self::assertCount(1, $changes);
        $stored = json_encode([$changes, DB::table('activity_log')->get()], JSON_THROW_ON_ERROR);
        self::assertStringNotContainsString('111122223333', $stored);
        self::assertStringNotContainsString('999988883333', $stored);
        self::assertStringContainsString('****3333', $stored);
    }

    public function test_query_exception_never_reports_bindings_even_in_debug_mode(): void
    {
        config(['app.debug' => true]);
        $exception = new QueryException('mysql', 'insert into messages(content) values (?)', ['private-sentinel-123'], new \PDOException('private-sentinel-123'));
        Log::spy();
        app(ExceptionHandler::class)->report($exception);
        Log::shouldHaveReceived('error')->once()->withArgs(fn ($message, $context): bool => ! str_contains(json_encode([$message, $context], JSON_THROW_ON_ERROR), 'private-sentinel-123'));
        $response = app(ExceptionHandler::class)->render(\Illuminate\Http\Request::create('/api/v1/test'), $exception);
        self::assertStringNotContainsString('private-sentinel-123', $response->getContent());
        self::assertSame(500, $response->getStatusCode());
    }

    public function test_wallet_retries_do_not_duplicate_charge_or_usage(): void
    {
        $hospital = Hospital::factory()->active()->approved()->create();
        $wallet = \App\Domains\HospitalWallet\Models\HospitalWallet::create(['hospital_id' => $hospital->id, 'paid_balance' => 0, 'service_balance' => 0]);
        $charge = new \App\Domains\HospitalWallet\Data\HospitalWalletChargeData($hospital->id, 10000, 10000, 1000, 11000, 'CARD', 'charge-test', now());
        $charges = app(\App\Domains\HospitalWallet\Actions\HospitalWalletChargeCompleteAction::class);
        self::assertSame($charges->execute($charge)->id, $charges->execute($charge)->id);
        $usage = new \App\Domains\HospitalWallet\Data\HospitalWalletUsageData($hospital->id, 4000, 0, 'usage-test', now(), $hospital, '검증 사용');
        $usages = app(\App\Domains\HospitalWallet\Actions\HospitalWalletUsageCompleteAction::class);
        self::assertSame($usages->execute($usage)->id, $usages->execute($usage)->id);
        self::assertSame(6000, $wallet->fresh()->paid_balance);
        self::assertSame(2, DB::table('hospital_wallet_transactions')->count());
        self::assertSame(2, DB::table('hospital_wallet_transaction_entries')->count());
        $this->expectException(CustomException::class);
        $usages->execute(new \App\Domains\HospitalWallet\Data\HospitalWalletUsageData($hospital->id, 5000, 0, 'usage-test', now(), $hospital, '변경된 요청'));
    }

    public function test_wallet_ledger_failure_rolls_back_balance_and_operation(): void
    {
        $hospital = Hospital::factory()->active()->approved()->create();
        $wallet = \App\Domains\HospitalWallet\Models\HospitalWallet::create(['hospital_id' => $hospital->id, 'paid_balance' => 10000, 'service_balance' => 0]);
        \Illuminate\Support\Facades\Event::listen('eloquent.creating: '.\App\Domains\HospitalWallet\Models\HospitalWalletTransactionEntry::class,
            static fn () => throw new \RuntimeException('격리된 원장 저장 장애'));
        try {
            app(\App\Domains\HospitalWallet\Actions\HospitalWalletUsageCompleteAction::class)->execute(
                new \App\Domains\HospitalWallet\Data\HospitalWalletUsageData($hospital->id, 4000, 0, 'rollback-test', now(), $hospital, '실패 검증'));
            self::fail('원장 저장 장애가 전파되어야 합니다.');
        } catch (\RuntimeException $exception) {
            self::assertSame('격리된 원장 저장 장애', $exception->getMessage());
        }
        self::assertSame(10000, $wallet->fresh()->paid_balance);
        self::assertSame(0, DB::table('hospital_wallet_operations')->count());
        self::assertSame(0, DB::table('hospital_wallet_transactions')->count());
    }

    public function test_wallet_usage_cannot_spend_points_reserved_for_refund(): void
    {
        $hospital = Hospital::factory()->active()->approved()->create();
        $wallet = \App\Domains\HospitalWallet\Models\HospitalWallet::create(['hospital_id' => $hospital->id, 'paid_balance' => 10000, 'reserved_paid_balance' => 8000, 'service_balance' => 0]);
        try {
            app(\App\Domains\HospitalWallet\Actions\HospitalWalletUsageCompleteAction::class)->execute(
                new \App\Domains\HospitalWallet\Data\HospitalWalletUsageData($hospital->id, 4000, 0, 'reserved-test', now(), $hospital, '예약 잔액 검증'));
            self::fail('예약 금액은 사용할 수 없어야 합니다.');
        } catch (CustomException $exception) {
            self::assertStringContainsString('잔액이 부족', $exception->getMessage());
        }
        self::assertSame(10000, $wallet->fresh()->paid_balance);
        self::assertSame(0, DB::table('hospital_wallet_operations')->count());
    }

    public function test_vote_rechecks_visibility_after_a_talk_has_been_hidden(): void
    {
        $talk = \App\Domains\Talk\Models\Talk::factory()->active()->create();
        $poll = $talk->poll()->create(['allow_multiple' => false]);
        $option = $poll->options()->create(['content' => '검증 선택지', 'sort_order' => 0]);
        \App\Domains\Talk\Models\Talk::whereKey($talk->id)->update(['status' => 'INACTIVE']);
        $this->expectException(CustomException::class);
        $this->expectExceptionMessage('투표할 수 없는 토크입니다.');
        app(\App\Domains\Talk\Actions\User\TalkPollVoteForUserAction::class)->execute(AccountUser::factory()->create(), $talk, ['option_ids' => [$option->id]]);
    }
}
