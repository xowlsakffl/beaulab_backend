<?php

declare(strict_types=1);

namespace Tests\Feature\Domains\HospitalChatBot;

use App\Common\Authorization\AccessPermissions;
use App\Domains\AccountHospital\Models\AccountHospital;
use App\Domains\AccountStaff\Models\AccountStaff;
use App\Domains\Hospital\Models\Hospital;
use App\Domains\HospitalChatBot\Actions\Common\HospitalChatBotMetricsAction;
use App\Domains\HospitalChatBot\Jobs\BroadcastHospitalChatBotMessageJob;
use App\Domains\HospitalChatBot\Models\HospitalChatBotConsultationMessage;
use App\Domains\HospitalChatBot\Support\HospitalChatBotConsultationStatus;
use App\Domains\HospitalChatBot\Support\HospitalChatBotLog;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\TestCase;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;
use Spatie\Permission\Models\Permission;

final class HospitalChatBotObservabilityTest extends TestCase
{
    use RefreshDatabase;

    public function createApplication(): Application
    {
        $app = parent::createApplication();

        if ($app->configurationIsCached() || config('database.connections.mysql.database') !== 'beaulab_testing') {
            throw new \LogicException('Feature tests require uncached configuration and the beaulab_testing database.');
        }

        return $app;
    }

    public function test_the_required_consultation_events_are_logged_with_a_stable_event_key(): void
    {
        $seen = $this->captureLogs();

        $staff = $this->staff();
        $this->loginStaff($staff);
        $this->openOperatingHours();

        $account = $this->hospitalAccount();
        $this->login('hospital', $account, 'http://localhost:3002');

        $id = (int) $this->postJson('/api/v1/hospital/hospital-chatbot/consultations')
            ->assertOk()->json('data.consultation.id');

        // 연타는 같은 상담으로 수렴한다. 얼마나 잦은지 세려면 별도 이벤트여야 한다.
        $this->postJson('/api/v1/hospital/hospital-chatbot/consultations')->assertOk();

        $this->loginStaff($staff);
        $this->postJson("/api/v1/staff/hospital-chatbot/consultations/{$id}/claim")->assertOk();
        $this->postJson("/api/v1/staff/hospital-chatbot/consultations/{$id}/end")->assertOk();

        $events = array_column($seen->getArrayCopy(), 'event');

        self::assertContains(HospitalChatBotLog::CONSULTATION_REQUESTED, $events);
        self::assertContains(HospitalChatBotLog::CONSULTATION_REQUEST_DEDUPED, $events);
        self::assertContains(HospitalChatBotLog::CONSULTATION_CLAIMED, $events);
        self::assertContains(HospitalChatBotLog::CONSULTATION_CLOSED, $events);

        /*
         * 메시지는 저장소 관례대로 한국어 문장이고, 집계용 키는 컨텍스트의 event다.
         * 문장을 고쳐도 집계가 끊기지 않아야 한다(PRD 16.6).
         */
        $requested = $this->firstWithEvent($seen, HospitalChatBotLog::CONSULTATION_REQUESTED);
        self::assertNotSame(HospitalChatBotLog::CONSULTATION_REQUESTED, $requested['message']);
        self::assertSame(HospitalChatBotLog::CONSULTATION_REQUESTED, $requested['context']['event']);
        self::assertSame($id, $requested['context']['consultation_id']);
    }

    public function test_a_claim_conflict_is_logged_as_its_own_event(): void
    {
        $seen = $this->captureLogs();

        $first = $this->staff();
        $second = $this->staff();

        $this->loginStaff($first);
        $this->openOperatingHours();

        $account = $this->hospitalAccount();
        $this->login('hospital', $account, 'http://localhost:3002');
        $id = (int) $this->postJson('/api/v1/hospital/hospital-chatbot/consultations')
            ->assertOk()->json('data.consultation.id');

        $this->loginStaff($first);
        $this->postJson("/api/v1/staff/hospital-chatbot/consultations/{$id}/claim")->assertOk();

        $this->loginStaff($second);
        $this->postJson("/api/v1/staff/hospital-chatbot/consultations/{$id}/claim")->assertStatus(409);

        // 충돌 수는 권장 지표다. 성공과 같은 이벤트로 묶이면 셀 수 없다.
        self::assertContains(HospitalChatBotLog::CONSULTATION_CLAIM_CONFLICT, array_column($seen->getArrayCopy(), 'event'));
    }

    public function test_operating_hours_failure_closes_the_chatbot_instead_of_erroring(): void
    {
        $seen = $this->captureLogs();

        $account = $this->hospitalAccount();
        $this->login('hospital', $account, 'http://localhost:3002');

        /*
         * 운영시간 조회 자체가 깨진 상황을 만든다.
         *
         * resolver는 잘못된 시각을 이미 정규식으로 걸러 내서 데이터만으로는 던지지
         * 않는다. 실제로 이 가드가 필요한 경우는 DB 쪽 사고다. 테이블을 지워
         * 조회가 실패하게 한다(RefreshDatabase가 다음 테스트에서 되돌린다).
         */
        \Illuminate\Support\Facades\Schema::drop('hospital_chatbot_operating_hours');

        $this->getJson('/api/v1/hospital/hospital-chatbot/availability')
            ->assertOk()
            ->assertJsonPath('data.is_available', false)
            ->assertJsonPath('data.reason', 'CONFIGURATION_UNAVAILABLE');

        self::assertContains(HospitalChatBotLog::OPERATING_HOURS_FAILED, array_column($seen->getArrayCopy(), 'event'));
    }

    public function test_metrics_report_current_gauges_and_whether_broadcasting_is_on(): void
    {
        $account = $this->hospitalAccount();

        $waiting = \App\Domains\HospitalChatBot\Models\HospitalChatBotConsultation::query()->create([
            'hospital_id' => (int) $account->hospital_id,
            'requested_by_account_hospital_id' => (int) $account->id,
            'status' => HospitalChatBotConsultationStatus::WAITING,
            'active_hospital_id' => (int) $account->hospital_id,
            'requested_at' => now()->subMinutes(4),
            'last_message_sequence' => 0,
            'version' => 0,
        ]);

        HospitalChatBotConsultationMessage::query()->create([
            'consultation_id' => (int) $waiting->id,
            'sequence' => 1,
            'sender_type' => 'HOSPITAL_USER',
            'sender_id' => (int) $account->id,
            'message_type' => 'TEXT',
            'content' => '안녕하세요',
            'broadcast_pending' => true,
            'created_at' => now()->subMinutes(2),
        ]);

        config(['broadcasting.default' => 'log']);
        $metrics = app(HospitalChatBotMetricsAction::class)->execute();

        self::assertSame(1, $metrics['waiting_count']);
        self::assertSame(0, $metrics['connected_count']);
        self::assertGreaterThanOrEqual(230, $metrics['longest_waiting_seconds']);
        self::assertSame(1, $metrics['pending_broadcast_count']);

        // 발행이 꺼져 있으면 pending은 줄지 않는다. 숫자만 보면 장애로 읽힌다.
        self::assertFalse($metrics['broadcast_enabled']);

        config(['broadcasting.default' => 'reverb']);
        self::assertTrue(app(HospitalChatBotMetricsAction::class)->execute()['broadcast_enabled']);
    }

    public function test_the_recovery_command_stays_quiet_while_broadcasting_is_disabled(): void
    {
        Queue::fake();

        $account = $this->hospitalAccount();
        $consultation = \App\Domains\HospitalChatBot\Models\HospitalChatBotConsultation::query()->create([
            'hospital_id' => (int) $account->hospital_id,
            'status' => HospitalChatBotConsultationStatus::CONNECTED,
            'active_hospital_id' => (int) $account->hospital_id,
            'requested_at' => now(),
            'connected_at' => now(),
            'last_message_sequence' => 0,
            'version' => 0,
        ]);

        HospitalChatBotConsultationMessage::query()->create([
            'consultation_id' => (int) $consultation->id,
            'sequence' => 1,
            'sender_type' => 'AGENT',
            'sender_id' => 1,
            'message_type' => 'TEXT',
            'content' => '네',
            'broadcast_pending' => true,
            'created_at' => now(),
        ]);

        /*
         * 이 커맨드는 매분 돈다. 발행이 꺼져 있는데도 집어 가면 큐 워커가 없는 환경에서
         * 같은 메시지를 매분 다시 밀어 넣어 Redis만 부푼다.
         */
        config(['broadcasting.default' => 'log']);
        $this->artisan('hospital-chatbot:dispatch-pending-broadcasts')->assertExitCode(0);
        Queue::assertNothingPushed();

        // 켜 두면 평소대로 집어 간다.
        config(['broadcasting.default' => 'reverb']);
        $this->artisan('hospital-chatbot:dispatch-pending-broadcasts')->assertExitCode(0);
        Queue::assertPushed(BroadcastHospitalChatBotMessageJob::class, 1);
    }

    /**
     * 남은 로그를 모은다.
     *
     * 배열로 돌려주면 값 복사라 클로저가 채워도 호출부에서는 비어 있다.
     * ArrayObject로 같은 객체를 계속 보게 한다.
     *
     * @return \ArrayObject<int, array<string, mixed>>
     */
    private function captureLogs(): \ArrayObject
    {
        /** @var \ArrayObject<int, array<string, mixed>> $seen */
        $seen = new \ArrayObject;

        Log::listen(function ($message) use ($seen): void {
            $seen[] = [
                'level' => $message->level,
                'message' => $message->message,
                'event' => $message->context['event'] ?? null,
                'context' => $message->context,
            ];
        });

        return $seen;
    }

    /**
     * @param  \ArrayObject<int, array<string, mixed>>  $seen
     * @return array<string, mixed>
     */
    private function firstWithEvent(\ArrayObject $seen, string $event): array
    {
        foreach ($seen as $row) {
            if (($row['event'] ?? null) === $event) {
                return $row;
            }
        }

        self::fail("로그에서 {$event} 를 찾지 못했다.");
    }

    private function login(string $actor, AccountStaff|AccountHospital $account, string $origin): void
    {
        $account->forceFill(['password' => 'password1234'])->save();
        $this->app['auth']->forgetGuards();
        $this->withCredentials()->withHeaders([
            'X-Beaulab-Client' => 'web',
            'Origin' => $origin,
        ]);
        $csrf = $this->getJson("/api/v1/{$actor}/auth/csrf")->assertOk();
        $this->withCookie("beaulab_{$actor}_session", $csrf->getCookie("beaulab_{$actor}_session")->getValue());
        $this->withHeader('X-CSRF-TOKEN', $csrf->json('data.csrf_token'));
        $this->app['auth']->forgetGuards();

        $login = $this->postJson("/api/v1/{$actor}/auth/login", [
            'nickname' => $account->nickname,
            'password' => 'password1234',
        ])->assertOk();
        $this->withCookie("beaulab_{$actor}_session", $login->getCookie("beaulab_{$actor}_session")->getValue());
        $this->app['auth']->forgetGuards();
    }

    private function hospitalAccount(): AccountHospital
    {
        return AccountHospital::factory()
            ->forHospital(Hospital::factory()->active()->create())
            ->active()
            ->create();
    }

    private function staff(): AccountStaff
    {
        $staff = AccountStaff::factory()->create();
        $staff->givePermissionTo(Permission::findOrCreate(AccessPermissions::COMMON_ACCESS, 'staff'));
        $staff->forceFill(['password' => 'password1234'])->save();

        return $staff;
    }

    private function loginStaff(AccountStaff $staff): void
    {
        $this->login('staff', $staff, 'http://localhost:3000');
    }

    private function openOperatingHours(): void
    {
        $this->putJson('/api/v1/staff/hospital-chatbot/operating-hours', [
            'weekly_schedule' => array_map(static fn (int $day): array => [
                'day_of_week' => $day,
                'is_open' => true,
                'opens_at' => '00:00',
                'closes_at' => '23:59',
            ], range(1, 7)),
            'closure_dates' => [],
            'is_force_closed' => false,
        ])->assertOk();
    }
}
