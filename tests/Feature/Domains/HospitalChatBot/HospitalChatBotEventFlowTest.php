<?php

declare(strict_types=1);

namespace Tests\Feature\Domains\HospitalChatBot;

use App\Domains\AccountHospital\Models\AccountHospital;
use App\Domains\AccountStaff\Models\AccountStaff;
use App\Domains\Hospital\Models\Hospital;
use App\Domains\HospitalChatBot\Models\HospitalChatBotEvent;
use App\Domains\HospitalChatBot\Models\HospitalChatBotQna;
use Carbon\CarbonImmutable;
use Database\Seeders\CategorySeeder;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\TestCase;
use Illuminate\Support\Facades\Artisan;

/**
 * 이용 이벤트 수집(대시보드 PRD §10, §15.1).
 */
final class HospitalChatBotEventFlowTest extends TestCase
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

    protected function setUp(): void
    {
        parent::setUp();

        // Hospital 팩토리가 활성 분과를 요구한다. 분과는 categories 테이블에서 읽는다.
        $this->seed(CategorySeeder::class);
    }

    public function test_hospital_user_records_open_and_view_events_with_session_identity(): void
    {
        $qna = $this->qna('충전금 환불 받고 싶어요');
        $account = $this->hospitalAccount();
        $this->login('hospital', $account, 'http://localhost:3002');

        $this->postJson('/api/v1/hospital/hospital-chatbot/events', [
            // 본문의 병의원·계정 값은 무시돼야 한다. 세션이 정한다.
            ['event_type' => 'WIDGET_OPENED', 'source_page_path' => '/dashboard', 'hospital_id' => 999, 'account_hospital_id' => 999],
            ['event_type' => 'QNA_VIEWED', 'qna_id' => $qna->id, 'source_page_path' => '  /dashboard  '],
        ])->assertStatus(202)->assertJsonPath('data.accepted', 2);

        $rows = HospitalChatBotEvent::query()->orderBy('id')->get();
        self::assertCount(2, $rows);
        self::assertSame((int) $account->hospital_id, $rows[0]->hospital_id);
        self::assertSame((int) $account->id, $rows[0]->account_hospital_id);
        self::assertNull($rows[0]->qna_id);
        self::assertSame($qna->id, $rows[1]->qna_id);
        self::assertSame('/dashboard', $rows[1]->source_page_path);
        // 시각은 서버가 찍는다.
        self::assertTrue($rows[0]->occurred_at->diffInSeconds(CarbonImmutable::now()) < 5);
    }

    public function test_open_event_drops_a_stray_qna_id_and_deleted_qnas_still_count(): void
    {
        $qna = $this->qna('삭제될 질문');
        $qna->delete();

        $account = $this->hospitalAccount();
        $this->login('hospital', $account, 'http://localhost:3002');

        $this->postJson('/api/v1/hospital/hospital-chatbot/events', [
            ['event_type' => 'WIDGET_OPENED', 'qna_id' => $qna->id],
            ['event_type' => 'QNA_VIEWED', 'qna_id' => $qna->id],
        ])->assertStatus(202)->assertJsonPath('data.accepted', 2);

        $rows = HospitalChatBotEvent::query()->orderBy('id')->get();
        self::assertNull($rows[0]->qna_id);
        self::assertSame($qna->id, $rows[1]->qna_id);
    }

    public function test_clicks_on_folder_nodes_are_dropped(): void
    {
        // SDK 트리에서 분류를 펼치는 것도 question-selected로 온다. 분류는 세지 않는다.
        $folder = $this->qna('충전금');
        $leaf = HospitalChatBotQna::query()->create([
            'parent_id' => $folder->id,
            'category' => '충전금',
            'question' => '충전금 환불 받고 싶어요',
            'question_key' => HospitalChatBotQna::questionKey('충전금 환불 받고 싶어요'),
            'answer' => '테스트 답변',
        ]);

        $account = $this->hospitalAccount();
        $this->login('hospital', $account, 'http://localhost:3002');

        $this->postJson('/api/v1/hospital/hospital-chatbot/events', [
            ['event_type' => 'QNA_VIEWED', 'qna_id' => $folder->id],
            ['event_type' => 'QNA_VIEWED', 'qna_id' => $leaf->id],
        ])->assertStatus(202)->assertJsonPath('data.accepted', 1);

        self::assertSame([$leaf->id], HospitalChatBotEvent::query()->pluck('qna_id')->all());
    }

    public function test_invalid_batches_are_rejected(): void
    {
        $account = $this->hospitalAccount();
        $this->login('hospital', $account, 'http://localhost:3002');

        // 모르는 종류
        $this->postJson('/api/v1/hospital/hospital-chatbot/events', [['event_type' => 'CATEGORY_VIEWED']])
            ->assertStatus(422);

        // 질문 클릭인데 질문이 없다
        $this->postJson('/api/v1/hospital/hospital-chatbot/events', [['event_type' => 'QNA_VIEWED']])
            ->assertStatus(422);

        // 없는 질문
        $this->postJson('/api/v1/hospital/hospital-chatbot/events', [['event_type' => 'QNA_VIEWED', 'qna_id' => 987654]])
            ->assertStatus(422);

        // 배열이 아니라 객체
        $this->postJson('/api/v1/hospital/hospital-chatbot/events', ['event_type' => 'WIDGET_OPENED'])
            ->assertStatus(422);

        // 21건
        $this->postJson('/api/v1/hospital/hospital-chatbot/events', array_fill(0, 21, ['event_type' => 'WIDGET_OPENED']))
            ->assertStatus(422)
            ->assertJsonPath('error.details.errors.events.0', '한 번에 최대 20건까지 보낼 수 있습니다.');

        self::assertSame(0, HospitalChatBotEvent::query()->count());
    }

    public function test_events_require_a_hospital_session(): void
    {
        $this->withHeaders(['X-Beaulab-Client' => 'web', 'Origin' => 'http://localhost:3002'])
            ->postJson('/api/v1/hospital/hospital-chatbot/events', [['event_type' => 'WIDGET_OPENED']])
            ->assertStatus(401);

        // 직원 세션으로는 병의원 경로에 못 들어온다. 인증은 됐으니 401이 아니라 403이다.
        $staff = AccountStaff::factory()->create();
        $this->login('staff', $staff, 'http://localhost:3000');
        $this->postJson('/api/v1/hospital/hospital-chatbot/events', [['event_type' => 'WIDGET_OPENED']])
            ->assertStatus(403);
    }

    public function test_events_are_rate_limited_per_account(): void
    {
        $account = $this->hospitalAccount();
        $this->login('hospital', $account, 'http://localhost:3002');

        for ($i = 0; $i < 120; $i++) {
            $this->postJson('/api/v1/hospital/hospital-chatbot/events', [['event_type' => 'WIDGET_OPENED']])
                ->assertStatus(202);
        }

        $this->postJson('/api/v1/hospital/hospital-chatbot/events', [['event_type' => 'WIDGET_OPENED']])
            ->assertStatus(429);
    }

    public function test_prune_removes_only_events_past_retention_in_chunks(): void
    {
        $account = $this->hospitalAccount();
        config(['hospital_chatbot.events.retention_days' => 180]);

        $rows = [];
        foreach ([181, 200, 365] as $daysAgo) {
            $rows[] = $this->eventRow($account, CarbonImmutable::now()->subDays($daysAgo));
        }
        foreach ([179, 30, 0] as $daysAgo) {
            $rows[] = $this->eventRow($account, CarbonImmutable::now()->subDays($daysAgo));
        }
        HospitalChatBotEvent::query()->insert($rows);

        // 묶음 1건으로 돌려도 다 지워져야 한다. 반복 조건이 틀리면 첫 묶음에서 멈춘다.
        Artisan::call('hospital-chatbot:prune-events', ['--chunk' => 1]);

        self::assertStringContainsString('older than 180 days: 3', Artisan::output());
        self::assertSame(3, HospitalChatBotEvent::query()->count());
        self::assertSame(0, HospitalChatBotEvent::query()->where('occurred_at', '<', CarbonImmutable::now()->subDays(180))->count());
    }

    /** @return array<string, mixed> */
    private function eventRow(AccountHospital $account, CarbonImmutable $occurredAt): array
    {
        return [
            'event_type' => HospitalChatBotEvent::TYPE_WIDGET_OPENED,
            'hospital_id' => (int) $account->hospital_id,
            'account_hospital_id' => (int) $account->id,
            'qna_id' => null,
            'source_page_path' => null,
            'occurred_at' => $occurredAt,
        ];
    }

    private function qna(string $question): HospitalChatBotQna
    {
        return HospitalChatBotQna::query()->create([
            'category' => '충전금',
            'question' => $question,
            'question_key' => HospitalChatBotQna::questionKey($question),
            'answer' => '테스트 답변',
        ]);
    }

    private function hospitalAccount(): AccountHospital
    {
        return AccountHospital::factory()
            ->forHospital(Hospital::factory()->active()->create())
            ->active()
            ->create();
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
}
