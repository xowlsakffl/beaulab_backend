<?php

declare(strict_types=1);

namespace Tests\Feature\Domains\HospitalChatBot;

use App\Common\Authorization\AccessPermissions;
use App\Domains\AccountHospital\Models\AccountHospital;
use App\Domains\AccountStaff\Models\AccountStaff;
use App\Domains\Hospital\Models\Hospital;
use App\Domains\HospitalChatBot\Models\HospitalChatBotConsultation;
use App\Domains\HospitalChatBot\Support\HospitalChatBotConsultationStatus;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\TestCase;
use Spatie\Permission\Models\Permission;

final class HospitalChatBotConsultationRecordFlowTest extends TestCase
{
    use RefreshDatabase, \Tests\Support\WithHospitalDepartment;

    public function createApplication(): Application
    {
        $app = parent::createApplication();

        if ($app->configurationIsCached() || config('database.connections.mysql.database') !== 'beaulab_testing') {
            throw new \LogicException('Feature tests require uncached configuration and the beaulab_testing database.');
        }

        return $app;
    }

    public function test_records_can_be_filtered_by_period_hospital_requester_and_id(): void
    {
        $staff = $this->loginStaff();

        $alpha = $this->consultation(['hospital_name_snapshot' => '알파의원', 'requester_name_snapshot' => '홍길동']);
        $beta = $this->consultation([
            'hospital_name_snapshot' => '베타의원',
            'requester_name_snapshot' => '김철수',
            'requested_at' => now()->subDays(40),
        ]);

        $all = 'status=ENDED,CANCELED&sort=recent';

        // 기간. to는 그날 끝까지 포함해야 한다. 00:00으로 자르면 마지막 날이 통째로 빠진다.
        $today = now()->format('Y-m-d');
        $this->getJson("/api/v1/staff/hospital-chatbot/consultations?{$all}&from={$today}&to={$today}")
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $alpha);

        $this->getJson("/api/v1/staff/hospital-chatbot/consultations?{$all}&hospital_id={$this->hospitalIdOf($beta)}")
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $beta);

        // 요청자는 표시명 일부로 찾는다. 계정을 지워도 기록은 남아야 해서 스냅샷을 본다.
        $this->getJson("/api/v1/staff/hospital-chatbot/consultations?{$all}&requester=".urlencode('철수'))
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $beta);

        $this->getJson("/api/v1/staff/hospital-chatbot/consultations?{$all}&consultation_id={$alpha}")
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $alpha);

        $this->getJson("/api/v1/staff/hospital-chatbot/consultations?{$all}&assigned_staff_id={$staff->id}")
            ->assertOk()
            ->assertJsonCount(2, 'data');
    }

    public function test_like_wildcards_in_the_requester_filter_are_taken_literally(): void
    {
        $this->loginStaff();

        $literal = $this->consultation(['requester_name_snapshot' => '100%달성']);
        $this->consultation(['requester_name_snapshot' => '홍길동']);

        // '%'를 와일드카드로 흘려보내면 전부 걸린다.
        $this->getJson('/api/v1/staff/hospital-chatbot/consultations?status=ENDED,CANCELED&requester='.urlencode('100%'))
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $literal);
    }

    public function test_the_record_sort_is_newest_first_while_the_queue_keeps_its_own_order(): void
    {
        $this->loginStaff();

        $older = $this->consultation(['requested_at' => now()->subDays(3)]);
        $newer = $this->consultation(['requested_at' => now()->subDay()]);

        $recent = $this->getJson('/api/v1/staff/hospital-chatbot/consultations?status=ENDED,CANCELED&sort=recent')
            ->assertOk()->json('data');
        self::assertSame([$newer, $older], array_column($recent, 'id'));

        // 대기 목록은 집어야 할 순서를 지킨다. 오래 기다린 것이 위다.
        $queue = $this->getJson('/api/v1/staff/hospital-chatbot/consultations?status=ENDED,CANCELED')
            ->assertOk()->json('data');
        self::assertSame([$older, $newer], array_column($queue, 'id'));
    }

    public function test_duration_is_empty_until_a_consultation_actually_starts(): void
    {
        $this->loginStaff();

        $canceled = $this->consultation([
            'status' => HospitalChatBotConsultationStatus::CANCELED,
            'connected_at' => null,
            'ended_at' => null,
            'canceled_at' => now(),
            'close_reason' => HospitalChatBotConsultationStatus::REASON_USER_CANCELED,
        ]);

        // 연결되지 않은 상담을 0초로 내보내면 "바로 끝난 상담"과 구분되지 않는다.
        $this->getJson("/api/v1/staff/hospital-chatbot/consultations/{$canceled}")
            ->assertOk()
            ->assertJsonPath('data.duration_seconds', null);

        $ended = $this->consultation(['connected_at' => now()->subMinutes(5), 'ended_at' => now()]);
        $this->getJson("/api/v1/staff/hospital-chatbot/consultations/{$ended}")
            ->assertOk()
            ->assertJsonPath('data.duration_seconds', 300);
    }

    public function test_export_returns_a_csv_that_matches_the_list_filters(): void
    {
        $this->loginStaff();

        $this->consultation(['hospital_name_snapshot' => '알파의원', 'requester_name_snapshot' => '홍길동']);
        $this->consultation(['hospital_name_snapshot' => '베타의원', 'requested_at' => now()->subDays(40)]);

        $today = now()->format('Y-m-d');
        $response = $this->get(
            "/api/v1/staff/hospital-chatbot/consultations/export?status=ENDED,CANCELED&from={$today}&to={$today}",
        )->assertOk();

        self::assertStringContainsString('text/csv', (string) $response->headers->get('content-type'));

        $csv = $response->streamedContent();

        // BOM이 없으면 엑셀이 한국어를 깨뜨린다.
        self::assertStringStartsWith("\xEF\xBB\xBF", $csv);
        self::assertStringContainsString('상담 ID', $csv);
        self::assertStringContainsString('알파의원', $csv);
        self::assertStringContainsString('홍길동', $csv);
        // 목록과 같은 기간 조건을 쓰므로 40일 전 건은 빠져야 한다.
        self::assertStringNotContainsString('베타의원', $csv);
        // 코드가 아니라 사람이 읽는 말로 나간다.
        self::assertStringContainsString('종료', $csv);
    }

    public function test_export_refuses_an_unbounded_or_oversized_period(): void
    {
        $this->loginStaff();

        $this->getJson('/api/v1/staff/hospital-chatbot/consultations/export?status=ENDED')
            ->assertStatus(422);

        $from = now()->subDays(400)->format('Y-m-d');
        $to = now()->format('Y-m-d');
        $this->getJson("/api/v1/staff/hospital-chatbot/consultations/export?from={$from}&to={$to}")
            ->assertStatus(422)
            ->assertJsonPath('error.code', 'INVALID_REQUEST');
    }

    public function test_export_refuses_more_rows_than_the_configured_limit(): void
    {
        $this->loginStaff();

        config(['hospital_chatbot.export.max_rows' => 1]);

        $this->consultation();
        $this->consultation();

        $today = now()->format('Y-m-d');
        $this->getJson("/api/v1/staff/hospital-chatbot/consultations/export?status=ENDED&from={$today}&to={$today}")
            ->assertStatus(422)
            ->assertJsonPath('error.code', 'INVALID_REQUEST');
    }

    public function test_export_writes_rfc_4180_csv_that_survives_a_round_trip(): void
    {
        $this->loginStaff();

        // 백슬래시 바로 뒤에 큰따옴표. PHP 기본 escape는 이 값을 깨뜨린다.
        $tricky = '경로\\"주의"';
        $this->consultation(['requester_name_snapshot' => $tricky]);

        $today = now()->format('Y-m-d');
        $csv = $this->get(
            "/api/v1/staff/hospital-chatbot/consultations/export?status=ENDED&from={$today}&to={$today}",
        )->assertOk()->streamedContent();

        $lines = array_values(array_filter(explode("\n", trim(str_replace("\xEF\xBB\xBF", '', $csv)))));
        $row = str_getcsv($lines[1], ',', '"', '');

        // 표준 리더로 다시 읽었을 때 쓴 값이 그대로 나와야 한다.
        self::assertSame($tricky, $row[2]);
    }

    public function test_export_neutralizes_formula_prefixes_in_user_supplied_text(): void
    {
        $this->loginStaff();
        $this->consultation([
            'hospital_name_snapshot' => '=1+1',
            'requester_name_snapshot' => '+123',
            'assigned_staff_name_snapshot' => '-123',
            'context_qna_question_snapshot' => '@SUM(1)',
            'source_page_path' => "\t=1+1",
        ]);
        $today = now()->format('Y-m-d');
        $csv = $this->get("/api/v1/staff/hospital-chatbot/consultations/export?status=ENDED&from={$today}&to={$today}")->assertOk()->streamedContent();
        $stream = fopen('php://memory', 'r+');
        fwrite($stream, $csv);
        rewind($stream);
        fgetcsv($stream, escape: '');
        $row = fgetcsv($stream, escape: '');
        fclose($stream);
        self::assertSame("'=1+1", $row[1]);
        self::assertSame("'+123", $row[2]);
        self::assertSame("'-123", $row[3]);
        self::assertSame("'@SUM(1)", $row[11]);
        self::assertSame("'\t=1+1", $row[12]);
    }

    public function test_export_matches_the_screen_for_waiting_rows_and_blank_snapshots(): void
    {
        $this->loginStaff();

        $waiting = $this->consultation([
            'status' => HospitalChatBotConsultationStatus::WAITING,
            'hospital_name_snapshot' => '',
            'connected_at' => null,
            'ended_at' => null,
            'close_reason' => null,
            'requested_at' => now()->subMinutes(3),
        ]);

        $screen = $this->getJson("/api/v1/staff/hospital-chatbot/consultations/{$waiting}")->assertOk()->json('data');

        $today = now()->format('Y-m-d');
        $csv = $this->get(
            "/api/v1/staff/hospital-chatbot/consultations/export?status=WAITING&from={$today}&to={$today}",
        )->assertOk()->streamedContent();

        $lines = array_values(array_filter(explode("\n", trim(str_replace("\xEF\xBB\xBF", '', $csv)))));
        $row = str_getcsv($lines[1], ',', '"', '');

        // 대기 중인 상담도 화면과 같은 대기 시간을 내보낸다. 빈 칸이면 두 화면이 어긋난다.
        self::assertNotSame('', $row[7]);
        self::assertEqualsWithDelta((int) $screen['waiting_seconds'], (int) $row[7], 2);

        // 병의원명이 빈 문자열이어도 화면과 같은 대체값을 쓴다.
        self::assertSame($screen['hospital']['name'] ?? "병의원 #{$this->hospitalIdOf($waiting)}", $row[1]);
        self::assertStringStartsWith('병의원 #', $row[1]);
    }

    public function test_export_keeps_the_last_day_even_when_given_mutable_dates(): void
    {
        $this->loginStaff();

        $today = now()->format('Y-m-d');
        $lateToday = $this->consultation(['requested_at' => now()->setTime(23, 30)]);

        // guardPeriod가 to를 되감으면 그날 늦은 상담이 통째로 빠진다.
        $csv = $this->get(
            "/api/v1/staff/hospital-chatbot/consultations/export?status=ENDED&from={$today}&to={$today}",
        )->assertOk()->streamedContent();

        self::assertStringContainsString((string) $lateToday, $csv);
    }

    public function test_manage_search_counts_sort_and_own_assignment(): void
    {
        $this->loginStaff();
        $ended = $this->consultation(['hospital_name_snapshot' => '검색의원', 'ended_at' => now()->subMinute()]);
        $connected = $this->consultation(['hospital_name_snapshot' => '검색의원', 'status' => 'CONNECTED', 'ended_at' => null, 'close_reason' => null]);
        $waiting = $this->consultation(['hospital_name_snapshot' => '검색의원', 'status' => 'WAITING', 'assigned_staff_id' => null, 'connected_at' => null, 'ended_at' => null, 'close_reason' => null]);
        $this->consultation(['hospital_name_snapshot' => '다른의원']);
        $url = '/api/v1/staff/hospital-chatbot/consultations?status=WAITING,CONNECTED,ENDED,CANCELED&sort=manage&q='.urlencode('검색');
        $response = $this->getJson($url)->assertOk()
            ->assertJsonPath('meta.status_counts.all', 3)
            ->assertJsonPath('meta.status_counts.active', 2)
            ->assertJsonPath('meta.status_counts.closed', 1);
        self::assertSame([$waiting, $connected, $ended], array_column($response->json('data'), 'id'));
        $otherStaff = AccountStaff::factory()->create();
        $this->consultation(['hospital_name_snapshot' => '검색의원', 'assigned_staff_id' => $otherStaff->id]);
        $mine = $this->getJson($url.'&mine=1')->assertOk()->assertJsonCount(3, 'data')
            ->assertJsonPath('meta.status_counts.active', 2);
        self::assertSame([$waiting, $connected, $ended], array_column($mine->json('data'), 'id'));
        $this->getJson($url.'&mine=1&close_reason=AGENT_COMPLETED')->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.id', $ended);
        $this->getJson('/api/v1/staff/hospital-chatbot/consultations?status=ENDED&q='.$this->hospitalIdOf($ended))
            ->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.id', $ended);
        $this->getJson($url.'&mine=1&per_page=1')->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('meta.status_counts.all', 3);
    }

    public function test_feed_cursor_keeps_order_and_returns_latest_message(): void
    {
        $this->loginStaff();
        $ended = $this->consultation();
        $waiting = $this->consultation(['status' => 'WAITING', 'assigned_staff_id' => null]);
        $connected = $this->consultation(['status' => 'CONNECTED']);
        foreach ([1 => '첫 메시지', 2 => '마지막 메시지'] as $sequence => $content) {
            \App\Domains\HospitalChatBot\Models\HospitalChatBotConsultationMessage::query()->create([
                'consultation_id' => $connected, 'sequence' => $sequence, 'sender_type' => 'AGENT',
                'message_type' => 'TEXT', 'content' => $content,
            ]);
        }
        $url = '/api/v1/staff/hospital-chatbot/consultations?feed=1&per_page=1&status=WAITING,CONNECTED,ENDED,CANCELED';
        $first = $this->getJson($url)->assertOk()->assertJsonPath('data.0.id', $waiting);
        $second = $this->getJson($url.'&cursor='.urlencode($first->json('meta.next_cursor')))
            ->assertOk()->assertJsonPath('data.0.id', $connected)->assertJsonPath('data.0.last_message', '마지막 메시지');
        $this->getJson($url.'&cursor='.urlencode($second->json('meta.next_cursor')))
            ->assertOk()->assertJsonPath('data.0.id', $ended)->assertJsonPath('meta.next_cursor', null);
    }

    public function test_presence_is_scoped_to_the_hospital_and_staff_ids_do_not_collide(): void
    {
        require base_path('routes/channels.php');
        $callback = \Illuminate\Support\Facades\Broadcast::getChannels()['hospital-chatbot.presence.{hospitalId}'];
        $hospital = AccountHospital::factory()->make(['id' => 7, 'hospital_id' => 123]);
        $staff = AccountStaff::factory()->create(['id' => 123]);
        $staff->givePermissionTo(Permission::findOrCreate(AccessPermissions::BEAULAB_CHATBOT_CONSULTATION_SHOW, 'staff'));
        self::assertSame(['id' => 'hospital:7', 'actor' => 'hospital'], $callback($hospital, 123));
        self::assertFalse($callback($hospital, 124));
        self::assertSame(['id' => 'staff:123', 'actor' => 'staff'], $callback($staff, 123));
        self::assertSame('staff:123', $staff->getAuthIdentifierForBroadcasting());
        self::assertFalse($callback(new \stdClass, 123));
    }

    /** @param array<string, mixed> $overrides */
    private function consultation(array $overrides = []): int
    {
        $account = AccountHospital::factory()
            ->forHospital(Hospital::factory()->active()->create(['name' => '테스트'.mb_chr(0xAC00 + Hospital::query()->count()).'의원']))
            ->active()
            ->create();

        $requestedAt = $overrides['requested_at'] ?? now()->subMinutes(10);

        return (int) HospitalChatBotConsultation::query()->create(array_merge([
            'hospital_id' => (int) $account->hospital_id,
            'hospital_name_snapshot' => $account->hospital?->name,
            'requested_by_account_hospital_id' => (int) $account->id,
            'requester_name_snapshot' => $account->name,
            'status' => HospitalChatBotConsultationStatus::ENDED,
            'active_hospital_id' => null,
            'assigned_staff_id' => $this->staffId,
            'assigned_staff_name_snapshot' => '김상담',
            'requested_at' => $requestedAt,
            'connected_at' => $requestedAt,
            'ended_at' => $requestedAt,
            'close_reason' => HospitalChatBotConsultationStatus::REASON_AGENT_COMPLETED,
            'last_message_sequence' => 0,
            'version' => 0,
        ], $overrides))->id;
    }

    private function hospitalIdOf(int $consultationId): int
    {
        return (int) HospitalChatBotConsultation::query()->whereKey($consultationId)->value('hospital_id');
    }

    private ?int $staffId = null;

    public function test_read_sequence_is_staff_scoped_and_never_moves_backwards(): void
    {
        $staff = $this->loginStaff();
        $id = $this->consultation(['last_message_sequence' => 3]);
        foreach ([1 => 'HOSPITAL_USER', 2 => 'AGENT', 3 => 'HOSPITAL_USER'] as $sequence => $sender) {
            \App\Domains\HospitalChatBot\Models\HospitalChatBotConsultationMessage::query()->create([
                'consultation_id' => $id, 'sequence' => $sequence, 'sender_type' => $sender,
                'message_type' => 'TEXT', 'content' => '읽음 테스트',
            ]);
        }
        $url = '/api/v1/staff/hospital-chatbot/consultations';
        $this->getJson($url.'?feed=1&status=ENDED')->assertOk()->assertJsonPath('data.0.unread_count', 2);
        $this->postJson($url."/{$id}/read", ['sequence' => 3])->assertOk()->assertJsonPath('data.unread_count', 0);
        $this->postJson($url."/{$id}/read", ['sequence' => 1])->assertOk()->assertJsonPath('data.unread_count', 0);
        $this->assertDatabaseHas('hospital_chatbot_consultation_reads', ['staff_id' => $staff->id, 'consultation_id' => $id, 'last_read_sequence' => 3]);
        $this->loginStaff();
        $this->getJson($url.'?feed=1&status=ENDED')->assertOk()->assertJsonPath('data.0.unread_count', 2);
    }

    public function test_claim_creates_one_greeting_even_when_retried(): void
    {
        $staff = $this->loginStaff();
        $id = $this->consultation(['status' => 'WAITING', 'assigned_staff_id' => null]);
        $url = "/api/v1/staff/hospital-chatbot/consultations/{$id}/claim";
        $this->postJson($url)->assertOk();
        $this->postJson($url)->assertOk();
        $messages = \App\Domains\HospitalChatBot\Models\HospitalChatBotConsultationMessage::query()->where('consultation_id', $id)->get();
        self::assertCount(1, $messages);
        self::assertSame('안녕하십니까. 뷰랩 상담사 '.$staff->name.'입니다.', $messages->first()->content);
        self::assertSame((int) $staff->id, (int) $messages->first()->sender_id);
    }

    private function loginStaff(): AccountStaff
    {
        $staff = AccountStaff::factory()->create();
        foreach ([AccessPermissions::BEAULAB_CHATBOT_CONSULTATION_SHOW, AccessPermissions::BEAULAB_CHATBOT_CONSULTATION_PROCESS] as $permission) {
            $staff->givePermissionTo(Permission::findOrCreate($permission, 'staff'));
        }
        $staff->forceFill(['password' => 'password1234'])->save();
        $this->staffId = (int) $staff->id;

        $this->app['auth']->forgetGuards();
        $this->withCredentials()->withHeaders(['X-Beaulab-Client' => 'web', 'Origin' => 'http://localhost:3000']);
        $csrf = $this->getJson('/api/v1/staff/auth/csrf')->assertOk();
        if ($cookie = $csrf->getCookie('beaulab_staff_session')) {
            $this->withCookie('beaulab_staff_session', $cookie->getValue());
        }
        $this->withHeader('X-CSRF-TOKEN', $csrf->json('data.csrf_token'));
        $this->app['auth']->forgetGuards();

        $login = $this->postJson('/api/v1/staff/auth/login', [
            'nickname' => $staff->nickname,
            'password' => 'password1234',
        ])->assertOk();
        $this->withCookie('beaulab_staff_session', $login->getCookie('beaulab_staff_session')->getValue());
        $this->app['auth']->forgetGuards();

        return $staff;
    }
}
