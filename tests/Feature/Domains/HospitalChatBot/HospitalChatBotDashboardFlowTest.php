<?php

declare(strict_types=1);

namespace Tests\Feature\Domains\HospitalChatBot;

use App\Common\Authorization\AccessPermissions;
use App\Domains\AccountHospital\Models\AccountHospital;
use App\Domains\AccountStaff\Models\AccountStaff;
use App\Domains\Hospital\Models\Hospital;
use App\Domains\HospitalChatBot\Models\HospitalChatBotQna;
use App\Domains\HospitalChatBot\Queries\Staff\HospitalChatBotDashboardForStaffQuery;
use Carbon\CarbonImmutable;
use Database\Seeders\CategorySeeder;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\TestCase;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;

/**
 * 대시보드 집계(대시보드 PRD §9, §12.2, §15.1).
 *
 * 상담·메시지·이벤트를 테이블에 직접 넣는다. API로 만들면 시각을 마음대로 둘 수 없다.
 */
final class HospitalChatBotDashboardFlowTest extends TestCase
{
    use RefreshDatabase;

    private const NOW = '2026-10-07 15:00:00';

    public function createApplication(): Application
    {
        $app = parent::createApplication();

        if ($app->configurationIsCached() || config('database.connections.mysql.database') !== 'beaulab_testing') {
            throw new \LogicException('Feature tests require uncached configuration and the beaulab_testing database.');
        }

        return $app;
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(CategorySeeder::class);
        CarbonImmutable::setTestNow(CarbonImmutable::parse(self::NOW, 'Asia/Seoul'));
    }

    protected function tearDown(): void
    {
        CarbonImmutable::setTestNow();
        parent::tearDown();
    }

    public function test_overview_aggregates_consultations_and_events_for_the_period(): void
    {
        [$alpha, $beta, $q1, $q2] = $this->scenario();
        $this->loginStaff($this->staff());

        $data = $this->getJson('/api/v1/staff/hospital-chatbot/dashboard?from=2026-10-01&to=2026-10-07')
            ->assertOk()->json('data');

        self::assertSame(['from' => '2026-10-01', 'to' => '2026-10-07', 'timezone' => 'Asia/Seoul'], $data['period']);

        // 모집단은 기간 내 요청 5건. 직전 같은 길이 기간(9/24~9/30)에 1건.
        $summary = $data['summary'];
        self::assertSame(5, $summary['requested']);
        self::assertSame(1, $summary['requested_previous']);
        self::assertSame(2, $summary['connected']);
        self::assertSame(1, $summary['timed_out']);
        self::assertSame(1, $summary['user_canceled']);
        self::assertSame(1, $summary['active']);
        // 연결된 둘: 100초, 300초. nearest-rank라 중앙값은 100, P90은 300.
        self::assertSame(['median' => 100, 'p90' => 300, 'sample' => 2], $summary['waiting_seconds']);
        // 자동 인사는 빼고 상담원이 직접 친 첫 메시지까지 48초. 인사만 있는 상담은 표본에서 빠진다.
        self::assertSame(['median' => 48, 'p90' => 48, 'sample' => 1], $summary['first_response_seconds']);
        self::assertSame(['median' => 500, 'p90' => 900, 'sample' => 2], $summary['consultation_seconds']);

        // 지금 대기 중은 기간과 무관한 현재 값. 14:50 요청이 15:00 기준 600초.
        self::assertSame(1, $data['live']['waiting']);
        self::assertSame(600, $data['live']['longest_waiting_seconds']);

        // 깔때기. 기간 밖(9/20) 이벤트는 수집 시작일에만 보인다.
        self::assertSame(
            ['collected_since' => '2026-09-20', 'opened' => 4, 'qna_viewed' => 3, 'requested' => 5],
            $data['funnel'],
        );

        // 날짜마다 한 줄, 빈 날은 0. 네 구간의 합이 요청 수와 같다.
        self::assertCount(7, $data['daily']);
        $byDay = collect($data['daily'])->keyBy('date');
        self::assertSame(['date' => '2026-10-02', 'ended' => 1, 'timed_out' => 1, 'user_canceled' => 0, 'active' => 0], $byDay['2026-10-02']);
        self::assertSame(['date' => '2026-10-03', 'ended' => 1, 'timed_out' => 0, 'user_canceled' => 0, 'active' => 0], $byDay['2026-10-03']);
        self::assertSame(['date' => '2026-10-04', 'ended' => 0, 'timed_out' => 0, 'user_canceled' => 1, 'active' => 0], $byDay['2026-10-04']);
        self::assertSame(['date' => '2026-10-07', 'ended' => 0, 'timed_out' => 0, 'user_canceled' => 0, 'active' => 1], $byDay['2026-10-07']);
        self::assertSame(['date' => '2026-10-01', 'ended' => 0, 'timed_out' => 0, 'user_canceled' => 0, 'active' => 0], $byDay['2026-10-01']);
        self::assertSame(5, collect($data['daily'])->sum(fn (array $d) => $d['ended'] + $d['timed_out'] + $d['user_canceled'] + $d['active']));

        // 요일×시. ISO 요일(월=1).
        $cells = collect($data['heatmap'])->map(fn (array $c) => "{$c['dow']}-{$c['hour']}:{$c['count']}")->all();
        $dow = fn (string $date): int => CarbonImmutable::parse($date, 'Asia/Seoul')->isoWeekday();
        self::assertEqualsCanonicalizing([
            "{$dow('2026-10-02')}-10:1", "{$dow('2026-10-02')}-11:1", "{$dow('2026-10-03')}-14:1",
            "{$dow('2026-10-04')}-9:1", "{$dow('2026-10-07')}-14:1",
        ], $cells);

        // 상담 유발 질문. Q1은 두 번 불렀고 두 번 봤다. Q2는 삭제됐지만 나와야 한다.
        self::assertSame([
            ['qna_id' => $q1->id, 'question' => '충전금 환불 받고 싶어요', 'requested' => 2, 'viewed' => 2, 'deleted' => false],
            ['qna_id' => $q2->id, 'question' => '이벤트 등록이 반려됐어요', 'requested' => 1, 'viewed' => 1, 'deleted' => true],
        ], $data['top_context_qnas']);
        self::assertSame(2, $data['requested_without_context']);

        self::assertSame([
            ['qna_id' => $q1->id, 'question' => '충전금 환불 받고 싶어요', 'viewed' => 2, 'requested' => 2, 'deleted' => false],
            ['qna_id' => $q2->id, 'question' => '이벤트 등록이 반려됐어요', 'viewed' => 1, 'requested' => 1, 'deleted' => true],
        ], $data['top_viewed_qnas']);

        self::assertSame([
            ['hospital_id' => $alpha->hospital_id, 'name' => '알파의원', 'requested' => 3, 'connected' => 1, 'opened' => 3, 'qna_viewed' => 2],
            ['hospital_id' => $beta->hospital_id, 'name' => '베타의원', 'requested' => 2, 'connected' => 1, 'opened' => 1, 'qna_viewed' => 1],
        ], $data['top_hospitals']);
    }

    public function test_period_defaults_to_the_last_thirty_days_and_is_capped(): void
    {
        $this->loginStaff($this->staff());

        $this->getJson('/api/v1/staff/hospital-chatbot/dashboard')
            ->assertOk()
            ->assertJsonPath('data.period.from', '2026-09-08')
            ->assertJsonPath('data.period.to', '2026-10-07')
            ->assertJsonPath('data.funnel.collected_since', null)
            ->assertJsonPath('data.summary.waiting_seconds', ['median' => null, 'p90' => null, 'sample' => 0])
            ->assertJsonCount(30, 'data.daily');

        $this->getJson('/api/v1/staff/hospital-chatbot/dashboard?from=2026-10-07&to=2026-10-01')->assertStatus(422);
        $this->getJson('/api/v1/staff/hospital-chatbot/dashboard?from=2025-10-05&to=2026-10-07')->assertStatus(422);
        $this->getJson('/api/v1/staff/hospital-chatbot/dashboard?from=2025-10-07&to=2026-10-07')->assertOk();
        $this->getJson('/api/v1/staff/hospital-chatbot/dashboard?from=abc')->assertStatus(422);
        // to 없이 from만 미래면 to가 오늘로 채워져 기간이 뒤집힌다. 빈 200이 아니라 422여야 한다.
        $this->getJson('/api/v1/staff/hospital-chatbot/dashboard?from=2027-01-01')->assertStatus(422);
    }

    public function test_dashboard_requires_a_staff_session(): void
    {
        $this->withHeaders(['X-Beaulab-Client' => 'web', 'Origin' => 'http://localhost:3000'])
            ->getJson('/api/v1/staff/hospital-chatbot/dashboard')->assertStatus(401);
    }

    public function test_percentiles_use_nearest_rank(): void
    {
        self::assertSame(['median' => null, 'p90' => null, 'sample' => 0], HospitalChatBotDashboardForStaffQuery::percentiles([]));
        self::assertSame(['median' => 7, 'p90' => 7, 'sample' => 1], HospitalChatBotDashboardForStaffQuery::percentiles([7]));
        self::assertSame(['median' => 5, 'p90' => 9, 'sample' => 10], HospitalChatBotDashboardForStaffQuery::percentiles(range(1, 10)));
        self::assertSame(['median' => 2, 'p90' => 3, 'sample' => 3], HospitalChatBotDashboardForStaffQuery::percentiles([3, 1, 2]));
    }

    /** @return array{0:AccountHospital, 1:AccountHospital, 2:HospitalChatBotQna, 3:HospitalChatBotQna} */
    private function scenario(): array
    {
        $alpha = $this->hospitalAccount('알파의원');
        $beta = $this->hospitalAccount('베타의원');
        $staff = AccountStaff::factory()->create(['name' => '김상담']);
        $q1 = $this->qna('충전금 환불 받고 싶어요');
        $q2 = $this->qna('이벤트 등록이 반려됐어요');

        // c1: 알파, 10/2 10:00 요청 → 10:01:40 연결 → 10:10 종료. 자동 인사 + 48초 뒤 상담원 첫 메시지.
        $c1 = $this->consultation($alpha, '알파의원', 'ENDED', '2026-10-02 10:00:00', [
            'connected_at' => '2026-10-02 10:01:40', 'ended_at' => '2026-10-02 10:10:00', 'close_reason' => 'AGENT_COMPLETED',
            'assigned_staff_id' => $staff->id, 'context_qna_id' => $q1->id, 'context_qna_question_snapshot' => $q1->question,
        ]);
        $this->message($c1, 1, 'AGENT', '2026-10-02 10:01:40', "greeting:{$c1}");
        $this->message($c1, 2, 'AGENT', '2026-10-02 10:02:28', 'a-1');
        $this->message($c1, 3, 'HOSPITAL_USER', '2026-10-02 10:03:00', 'h-1');

        // c2: 알파, 10/2 11:00 요청, 대기 초과.
        $this->consultation($alpha, '알파의원', 'CANCELED', '2026-10-02 11:00:00', [
            'canceled_at' => '2026-10-02 11:30:00', 'close_reason' => 'WAITING_TIMEOUT',
            'context_qna_id' => $q1->id, 'context_qna_question_snapshot' => $q1->question,
        ]);

        // c3: 베타, 10/3 14:00 요청 → 14:05 연결 → 14:20 사용자 종료. 자동 인사만 있다.
        $c3 = $this->consultation($beta, '베타의원', 'ENDED', '2026-10-03 14:00:00', [
            'connected_at' => '2026-10-03 14:05:00', 'ended_at' => '2026-10-03 14:20:00', 'close_reason' => 'USER_ENDED',
            'assigned_staff_id' => $staff->id, 'context_qna_id' => $q2->id, 'context_qna_question_snapshot' => $q2->question,
        ]);
        $this->message($c3, 1, 'AGENT', '2026-10-03 14:05:00', "greeting:{$c3}");

        // c4: 베타, 10/4 09:00 요청, 사용자 취소, 질문 안 봄.
        $this->consultation($beta, '베타의원', 'CANCELED', '2026-10-04 09:00:00', [
            'canceled_at' => '2026-10-04 09:02:00', 'close_reason' => 'USER_CANCELED',
        ]);

        // c5: 알파, 10/7 14:50 요청, 지금 대기 중.
        $this->consultation($alpha, '알파의원', 'WAITING', '2026-10-07 14:50:00', ['active_hospital_id' => $alpha->hospital_id]);

        // c6: 직전 기간(9/25). 이번 기간 집계에는 안 들어가고 "직전 대비"에만 잡힌다.
        $this->consultation($beta, '베타의원', 'CANCELED', '2026-09-25 10:00:00', [
            'canceled_at' => '2026-09-25 10:30:00', 'close_reason' => 'WAITING_TIMEOUT',
        ]);

        // 이벤트. 기간 밖 하나는 수집 시작일만 정한다.
        $this->event($alpha, 'WIDGET_OPENED', '2026-09-20 09:00:00');
        foreach (['2026-10-02 09:58:00', '2026-10-02 10:59:00', '2026-10-07 14:49:00'] as $at) {
            $this->event($alpha, 'WIDGET_OPENED', $at);
        }
        $this->event($alpha, 'QNA_VIEWED', '2026-10-02 09:59:00', $q1->id);
        $this->event($alpha, 'QNA_VIEWED', '2026-10-02 10:59:30', $q1->id);
        $this->event($beta, 'WIDGET_OPENED', '2026-10-03 13:58:00');
        $this->event($beta, 'QNA_VIEWED', '2026-10-03 13:59:00', $q2->id);

        // Q2는 집계 뒤 삭제됐다. 그래도 표에는 나와야 한다.
        $q2->delete();

        return [$alpha, $beta, $q1, $q2];
    }

    /** @param  array<string, mixed>  $extra */
    private function consultation(AccountHospital $account, string $hospitalName, string $status, string $requestedAt, array $extra = []): int
    {
        return (int) DB::table('hospital_chatbot_consultations')->insertGetId(array_merge([
            'hospital_id' => $account->hospital_id,
            'hospital_name_snapshot' => $hospitalName,
            'requested_by_account_hospital_id' => $account->id,
            'requester_name_snapshot' => $account->name,
            'status' => $status,
            'requested_at' => $requestedAt,
            'last_message_sequence' => 0,
            'version' => 0,
            'created_at' => $requestedAt,
            'updated_at' => $requestedAt,
        ], $extra));
    }

    private function message(int $consultationId, int $sequence, string $sender, string $createdAt, string $clientMessageId): void
    {
        DB::table('hospital_chatbot_consultation_messages')->insert([
            'consultation_id' => $consultationId,
            'sequence' => $sequence,
            'sender_type' => $sender,
            'sender_id' => null,
            'sender_name_snapshot' => $sender === 'AGENT' ? '김상담' : '요청자',
            'client_message_id' => $clientMessageId,
            'message_type' => 'TEXT',
            'content' => '메시지',
            'broadcast_pending' => false,
            'created_at' => $createdAt,
        ]);
    }

    private function event(AccountHospital $account, string $type, string $occurredAt, ?int $qnaId = null): void
    {
        DB::table('hospital_chatbot_events')->insert([
            'event_type' => $type,
            'hospital_id' => $account->hospital_id,
            'account_hospital_id' => $account->id,
            'qna_id' => $qnaId,
            'source_page_path' => '/dashboard',
            'occurred_at' => $occurredAt,
        ]);
    }

    private function qna(string $question): HospitalChatBotQna
    {
        return HospitalChatBotQna::query()->create([
            'category' => '테스트',
            'question' => $question,
            'question_key' => HospitalChatBotQna::questionKey($question),
            'answer' => '테스트 답변',
        ]);
    }

    private function hospitalAccount(string $hospitalName): AccountHospital
    {
        return AccountHospital::factory()
            ->forHospital(Hospital::factory()->active()->create(['name' => $hospitalName]))
            ->active()
            ->create();
    }

    private function staff(): AccountStaff
    {
        $staff = AccountStaff::factory()->create();
        $staff->givePermissionTo(Permission::findOrCreate(AccessPermissions::COMMON_ACCESS, 'staff'));

        return $staff;
    }

    private function loginStaff(AccountStaff $staff): void
    {
        $staff->forceFill(['password' => 'password1234'])->save();
        $this->app['auth']->forgetGuards();
        $this->withCredentials()->withHeaders(['X-Beaulab-Client' => 'web', 'Origin' => 'http://localhost:3000']);
        $csrf = $this->getJson('/api/v1/staff/auth/csrf')->assertOk();
        $this->withCookie('beaulab_staff_session', $csrf->getCookie('beaulab_staff_session')->getValue());
        $this->withHeader('X-CSRF-TOKEN', $csrf->json('data.csrf_token'));
        $this->app['auth']->forgetGuards();

        $login = $this->postJson('/api/v1/staff/auth/login', ['nickname' => $staff->nickname, 'password' => 'password1234'])->assertOk();
        $this->withCookie('beaulab_staff_session', $login->getCookie('beaulab_staff_session')->getValue());
        $this->app['auth']->forgetGuards();
    }
}
