<?php

declare(strict_types=1);

namespace Tests\Feature\Domains\HospitalChatBot;

use App\Common\Authorization\AccessPermissions;
use App\Domains\AccountHospital\Models\AccountHospital;
use App\Domains\AccountStaff\Models\AccountStaff;
use App\Domains\Hospital\Models\Hospital;
use App\Domains\HospitalChatBot\Events\HospitalChatBotConsultationMessageCreated;
use App\Domains\HospitalChatBot\Jobs\BroadcastHospitalChatBotMessageJob;
use App\Domains\HospitalChatBot\Models\HospitalChatBotConsultation;
use App\Domains\HospitalChatBot\Models\HospitalChatBotConsultationMessage;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\TestCase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Event;
use Spatie\Permission\Models\Permission;

final class HospitalChatBotConsultationMessageFlowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        \App\Domains\Common\Category\Models\Category::create(['domain' => 'HOSPITAL_DEPARTMENT', 'depth' => 1,
            'code' => 'PLASTIC_SURGERY', 'name' => '성형외과', 'full_path' => '성형외과', 'status' => 'ACTIVE']);
    }

    public function createApplication(): Application
    {
        $app = parent::createApplication();

        if ($app->configurationIsCached() || config('database.connections.mysql.database') !== 'beaulab_testing') {
            throw new \LogicException('Feature tests require uncached configuration and the beaulab_testing database.');
        }

        return $app;
    }

    public function test_navigation_badge_tracks_personal_notifications_and_conversation_read(): void
    {
        Bus::fake();
        [$staff, $account, $id] = $this->connectedConsultation();
        $badge = fn () => $this->getJson('/api/v1/staff/navigation-badges')->assertOk()
            ->json('data.badges./hospital-chatbot/consultations');
        self::assertFalse($badge()['has_new']);
        $this->login('hospital', $account, 'http://localhost:3002');
        $this->postJson("/api/v1/hospital/hospital-chatbot/consultations/{$id}/messages", ['content' => '확인 부탁드립니다.'])->assertOk();
        foreach (\Illuminate\Support\Facades\DB::table('staff_notification_outbox')->whereNull('processed_at')->pluck('id') as $outboxId) {
            app(\App\Domains\Common\Notification\Actions\StaffNotificationOutboxAction::class)->process((int) $outboxId);
        }
        $this->login('staff', $staff, 'http://localhost:3000');
        self::assertSame(['count' => 1, 'has_new' => true], $badge());
        $this->getJson('/api/v1/staff/notifications/unread-count')->assertOk()->assertJsonPath('data.unread_count', 1);
        $targets = [['target_type' => 'hospital_chatbot_consultation', 'target_id' => $id]];
        $batch = fn () => $this->postJson('/api/v1/staff/notifications/unread-targets', ['targets' => $targets])->assertOk()->json('data.targets.0.unread_count');
        self::assertSame(1, $batch());
        $notifications = app(\App\Domains\Common\Notification\Actions\StaffNotificationForStaffAction::class);
        $snapshot = $notifications->unread($staff, $targets[0]);
        self::assertSame(0, $notifications->readAll($staff, $snapshot['read_all_token'])['updated_count']);
        self::assertSame(1, $batch());
        $other = $this->staffWithPermissions([AccessPermissions::BEAULAB_CHATBOT_CONSULTATION_SHOW, AccessPermissions::BEAULAB_CHATBOT_CONSULTATION_PROCESS, AccessPermissions::BEAULAB_CHATBOT_OPERATION_SHOW, AccessPermissions::BEAULAB_CHATBOT_OPERATION_UPDATE]);
        self::assertSame(0, app(\App\Domains\HospitalChatBot\Queries\Staff\HospitalChatBotConsultationReadForStaffQuery::class)->attentionCount($other->id));
        self::assertSame(0, $notifications->unreadTargets($other, $targets)['targets'][0]['unread_count']);
        HospitalChatBotConsultation::query()->whereKey($id)->update(['assigned_staff_id' => $other->id]);
        self::assertSame(0, $batch());
        HospitalChatBotConsultation::query()->whereKey($id)->update(['assigned_staff_id' => $staff->id]);
        self::assertSame(1, $batch());
        $version = (int) \Illuminate\Support\Facades\DB::table('staff_notification_states')->where('staff_id', $staff->id)->value('version');
        $this->postJson("/api/v1/staff/hospital-chatbot/consultations/{$id}/read", ['sequence' => 2])->assertOk();
        self::assertFalse($badge()['has_new']);
        self::assertSame(0, $batch());
        self::assertSame($version + 1, (int) \Illuminate\Support\Facades\DB::table('staff_notification_states')->where('staff_id', $staff->id)->value('version'));
        HospitalChatBotConsultation::query()->whereKey($id)->update(['status' => 'WAITING', 'assigned_staff_id' => null]);
        // 업무가 대기 상태여도 새 알림을 받지 않았다면 메뉴 숫자를 만들지 않는다.
        self::assertFalse($badge()['has_new']);
        HospitalChatBotConsultation::query()->whereKey($id)->update(['status' => 'CANCELED']);
        self::assertFalse($badge()['has_new']);
    }

    public function test_both_sides_exchange_messages_with_a_single_shared_sequence(): void
    {
        Bus::fake();

        [$staff, $account, $consultationId] = $this->connectedConsultation();

        $this->login('hospital', $account, 'http://localhost:3002');
        $this->postJson("/api/v1/hospital/hospital-chatbot/consultations/{$consultationId}/messages", [
            'content' => '안녕하세요, 문의가 있습니다.',
        ])
            ->assertOk()
            ->assertJsonPath('data.sequence', 2)
            ->assertJsonPath('data.sender_type', HospitalChatBotConsultationMessage::SENDER_HOSPITAL_USER);

        $this->login('staff', $staff, 'http://localhost:3000');
        $this->postJson("/api/v1/staff/hospital-chatbot/consultations/{$consultationId}/messages", [
            'content' => '네, 말씀해 주세요.',
        ])
            ->assertOk()
            ->assertJsonPath('data.sequence', 3)
            ->assertJsonPath('data.sender_type', HospitalChatBotConsultationMessage::SENDER_AGENT);

        // 양쪽이 같은 순번 공간을 쓰므로 순서가 하나로 정해진다.
        $messages = $this->getJson("/api/v1/staff/hospital-chatbot/consultations/{$consultationId}/messages")
            ->assertOk()
            ->assertJsonPath('meta.last_sequence', 3)
            ->json('data');

        self::assertSame([1, 2, 3], array_column($messages, 'sequence'));
        // 발신자 계정 ID는 내보내지 않는다.
        self::assertArrayNotHasKey('sender_id', $messages[0]);

        // 병의원 사용자도 같은 대화를 본다.
        $this->login('hospital', $account, 'http://localhost:3002');
        $this->getJson("/api/v1/hospital/hospital-chatbot/consultations/{$consultationId}/messages")
            ->assertOk()
            ->assertJsonCount(3, 'data');

        Bus::assertDispatchedTimes(BroadcastHospitalChatBotMessageJob::class, 3);
    }

    public function test_the_same_client_message_id_is_stored_once(): void
    {
        Bus::fake();

        [, $account, $consultationId] = $this->connectedConsultation();
        $this->login('hospital', $account, 'http://localhost:3002');

        $payload = ['content' => '재시도 되는 메시지', 'client_message_id' => 'retry-key-1'];

        $first = $this->postJson("/api/v1/hospital/hospital-chatbot/consultations/{$consultationId}/messages", $payload)
            ->assertOk()->json('data.id');

        // 응답을 놓친 클라이언트가 같은 키로 다시 보낸다.
        $second = $this->postJson("/api/v1/hospital/hospital-chatbot/consultations/{$consultationId}/messages", $payload)
            ->assertOk()->json('data.id');

        self::assertSame($first, $second);
        self::assertSame(2, HospitalChatBotConsultationMessage::query()->count());
        // 워커가 아직 전달하지 않았다면 재시도도 복구 기회가 된다.
        Bus::assertDispatchedTimes(BroadcastHospitalChatBotMessageJob::class, 3);
        HospitalChatBotConsultationMessage::whereKey($first)->update(['broadcast_pending' => false]);
        $this->postJson("/api/v1/hospital/hospital-chatbot/consultations/{$consultationId}/messages", $payload)->assertOk();
        Bus::assertDispatchedTimes(BroadcastHospitalChatBotMessageJob::class, 3);
    }

    public function test_reusing_a_message_key_with_different_content_is_rejected(): void
    {
        Bus::fake();
        [, $account, $id] = $this->connectedConsultation();
        $this->login('hospital', $account, 'http://localhost:3002');
        $url = "/api/v1/hospital/hospital-chatbot/consultations/{$id}/messages";
        $this->postJson($url, ['content' => '첫 내용', 'client_message_id' => 'same-key'])->assertOk();
        $this->postJson($url, ['content' => '다른 내용', 'client_message_id' => 'same-key'])
            ->assertStatus(422)->assertJsonPath('error.code', 'INVALID_REQUEST');
        self::assertSame(1, HospitalChatBotConsultationMessage::where('client_message_id', 'same-key')->count());
    }

    public function test_queue_failure_does_not_turn_a_saved_message_into_an_api_failure(): void
    {
        Bus::fake();
        [, $account, $id] = $this->connectedConsultation();
        $this->login('hospital', $account, 'http://localhost:3002');
        $dispatcher = Bus::getFacadeRoot();
        Bus::shouldReceive('dispatch')->andThrow(new \RuntimeException('격리된 큐 장애'));
        $url = "/api/v1/hospital/hospital-chatbot/consultations/{$id}/messages";
        $payload = ['content' => '저장 후 재전달', 'client_message_id' => 'queue-failure'];
        $messageId = $this->postJson($url, $payload)->assertOk()->json('data.id');
        self::assertTrue(HospitalChatBotConsultationMessage::findOrFail($messageId)->broadcast_pending);
        Bus::swap($dispatcher);
        Bus::fake();
        self::assertSame($messageId, $this->postJson($url, $payload)->assertOk()->json('data.id'));
        self::assertSame(1, HospitalChatBotConsultationMessage::where('client_message_id', 'queue-failure')->count());
        Bus::assertDispatched(BroadcastHospitalChatBotMessageJob::class);
    }

    public function test_connection_uses_saved_greeting_and_retry_does_not_duplicate_it(): void
    {
        Bus::fake();
        \App\Domains\HospitalChatBot\Models\HospitalChatBotMessageSetting::query()->create([
            'id' => 1,
            ...\App\Domains\HospitalChatBot\Models\HospitalChatBotMessageSetting::defaults(),
            'connection_message' => '안녕하세요. {상담원명} 상담사가 도와드리겠습니다.',
        ]);
        [$staff, , $consultationId] = $this->connectedConsultation();
        $consultation = HospitalChatBotConsultation::query()->findOrFail($consultationId);
        $message = HospitalChatBotConsultationMessage::query()->where('consultation_id', $consultationId)->firstOrFail();
        self::assertSame('안녕하세요. '.$consultation->assigned_staff_name_snapshot.' 상담사가 도와드리겠습니다.', $message->content);
        self::assertFalse(app(\App\Domains\HospitalChatBot\Support\HospitalChatBotConsultationTransition::class)->claim($consultationId, $staff->id, $staff->name));
        self::assertSame(1, HospitalChatBotConsultationMessage::query()->where('consultation_id', $consultationId)->count());
    }

    public function test_messages_are_rejected_outside_a_connected_consultation(): void
    {
        Bus::fake();

        $staff = $this->staffWithPermissions([AccessPermissions::BEAULAB_CHATBOT_CONSULTATION_SHOW, AccessPermissions::BEAULAB_CHATBOT_CONSULTATION_PROCESS, AccessPermissions::BEAULAB_CHATBOT_OPERATION_SHOW, AccessPermissions::BEAULAB_CHATBOT_OPERATION_UPDATE]);
        $this->login('staff', $staff, 'http://localhost:3000');
        $this->openOperatingHours();

        $account = $this->hospitalAccount();
        $this->login('hospital', $account, 'http://localhost:3002');
        $consultationId = (int) $this->postJson('/api/v1/hospital/hospital-chatbot/consultations')
            ->assertOk()->json('data.consultation.id');

        // 대기 중에는 보낼 수 없다.
        $this->postJson("/api/v1/hospital/hospital-chatbot/consultations/{$consultationId}/messages", [
            'content' => '아직 대기 중입니다',
        ])
            ->assertStatus(409)
            ->assertJsonPath('error.code', 'CONSULTATION_NOT_ACTIVE');

        $this->login('staff', $staff, 'http://localhost:3000');
        $this->postJson("/api/v1/staff/hospital-chatbot/consultations/{$consultationId}/claim")->assertOk();
        $this->postJson("/api/v1/staff/hospital-chatbot/consultations/{$consultationId}/end")->assertOk();

        // 종료된 뒤에도 보낼 수 없다.
        $this->login('hospital', $account, 'http://localhost:3002');
        $this->postJson("/api/v1/hospital/hospital-chatbot/consultations/{$consultationId}/messages", [
            'content' => '종료 후 메시지',
        ])
            ->assertStatus(409)
            ->assertJsonPath('error.code', 'CONSULTATION_NOT_ACTIVE');

        $this->getJson("/api/v1/hospital/hospital-chatbot/consultations/{$consultationId}/messages")
            ->assertOk()->assertJsonCount(1, 'data');
        $this->login('hospital', $this->hospitalAccount(), 'http://localhost:3002');
        $this->getJson("/api/v1/hospital/hospital-chatbot/consultations/{$consultationId}/messages")->assertNotFound();

        self::assertSame(1, HospitalChatBotConsultationMessage::query()->count());
    }

    public function test_only_the_assigned_agent_can_send_and_other_hospitals_are_blocked(): void
    {
        Bus::fake();

        [, $account, $consultationId] = $this->connectedConsultation();

        $other = $this->staffWithPermissions([AccessPermissions::BEAULAB_CHATBOT_CONSULTATION_SHOW, AccessPermissions::BEAULAB_CHATBOT_CONSULTATION_PROCESS, AccessPermissions::BEAULAB_CHATBOT_OPERATION_SHOW, AccessPermissions::BEAULAB_CHATBOT_OPERATION_UPDATE]);
        $this->login('staff', $other, 'http://localhost:3000');
        $this->postJson("/api/v1/staff/hospital-chatbot/consultations/{$consultationId}/messages", [
            'content' => '담당이 아닌 상담원',
        ])->assertStatus(403);

        // 담당이 아니어도 읽기는 가능하다. 목록에서 상세로 들어가 볼 수 있어야 한다.
        $this->getJson("/api/v1/staff/hospital-chatbot/consultations/{$consultationId}/messages")->assertOk();

        $stranger = $this->hospitalAccount();
        $this->login('hospital', $stranger, 'http://localhost:3002');
        $this->postJson("/api/v1/hospital/hospital-chatbot/consultations/{$consultationId}/messages", [
            'content' => '남의 상담',
        ])->assertStatus(404);
        $this->getJson("/api/v1/hospital/hospital-chatbot/consultations/{$consultationId}/messages")
            ->assertStatus(404);

        unset($account);
        self::assertSame(1, HospitalChatBotConsultationMessage::query()->count());
    }

    public function test_after_sequence_returns_only_the_missing_tail(): void
    {
        Bus::fake();

        [, $account, $consultationId] = $this->connectedConsultation();
        $this->login('hospital', $account, 'http://localhost:3002');

        foreach (['첫번째', '두번째', '세번째'] as $content) {
            $this->postJson("/api/v1/hospital/hospital-chatbot/consultations/{$consultationId}/messages", [
                'content' => $content,
            ])->assertOk();
        }

        // 재접속한 클라이언트가 1번까지 가지고 있다고 알리면 그 뒤만 받는다.
        $response = $this->getJson(
            "/api/v1/hospital/hospital-chatbot/consultations/{$consultationId}/messages?after_sequence=1",
        )->assertOk();

        self::assertSame([2, 3, 4], array_column($response->json('data'), 'sequence'));
        self::assertSame(4, $response->json('meta.last_sequence'));

        // 빠진 것이 없으면 빈 목록과 요청한 지점을 그대로 돌려준다.
        $upToDate = $this->getJson(
            "/api/v1/hospital/hospital-chatbot/consultations/{$consultationId}/messages?after_sequence=4",
        )->assertOk();

        self::assertSame([], $upToDate->json('data'));
        self::assertSame(4, $upToDate->json('meta.last_sequence'));
    }

    public function test_both_actors_can_page_back_through_more_than_200_messages(): void
    {
        Bus::fake();
        [$staff, $account, $consultationId] = $this->connectedConsultation();
        foreach (range(2, 206) as $sequence) {
            HospitalChatBotConsultationMessage::query()->create([
                'consultation_id' => $consultationId,
                'sequence' => $sequence,
                'sender_type' => 'AGENT',
                'sender_id' => $staff->id,
                'message_type' => 'TEXT',
                'content' => '상담 메시지 '.$sequence,
                'created_at' => now(),
            ]);
        }

        foreach ([['staff', $staff, 'http://localhost:3000'], ['hospital', $account, 'http://localhost:3002']] as [$actor, $user, $origin]) {
            $this->login($actor, $user, $origin);
            $url = "/api/v1/{$actor}/hospital-chatbot/consultations/{$consultationId}/messages";
            $latest = $this->getJson($url)->assertOk()->assertJsonPath('meta.has_older', true);
            self::assertSame(range(7, 206), array_column($latest->json('data'), 'sequence'));
            $older = $this->getJson($url.'?before_sequence=7')->assertOk()->assertJsonPath('meta.has_older', false);
            self::assertSame(range(1, 6), array_column($older->json('data'), 'sequence'));
            $this->getJson($url.'?before_sequence=7&after_sequence=1')->assertStatus(422);
            $this->getJson($url.'?before_sequence=0')->assertStatus(422);
        }
    }

    public function test_horizon_consumes_the_chatbot_job_queue_in_supported_environments(): void
    {
        $job = new BroadcastHospitalChatBotMessageJob(1);
        self::assertContains($job->queue, config('horizon.defaults.supervisor-hospital-chatbot.queue'));
        foreach (['local', 'production'] as $environment) {
            self::assertNotNull(config("horizon.environments.{$environment}.supervisor-hospital-chatbot"));
        }
    }

    public function test_a_message_that_missed_its_broadcast_is_picked_up_again(): void
    {
        Bus::fake();

        [, $account, $consultationId] = $this->connectedConsultation();
        $this->login('hospital', $account, 'http://localhost:3002');

        $messageId = (int) $this->postJson("/api/v1/hospital/hospital-chatbot/consultations/{$consultationId}/messages", [
            'content' => '발행을 놓친 메시지',
        ])->assertOk()->json('data.id');

        // 저장은 됐지만 아직 발행 전 표식이 남아 있다.
        self::assertTrue(
            (bool) HospitalChatBotConsultationMessage::query()->whereKey($messageId)->value('broadcast_pending'),
        );

        $this->artisan('hospital-chatbot:dispatch-pending-broadcasts')->assertExitCode(0);
        Bus::assertDispatched(BroadcastHospitalChatBotMessageJob::class);

        // 실제로 Job을 돌리면 발행하고 표식을 내린다.
        Event::fake([HospitalChatBotConsultationMessageCreated::class]);
        (new BroadcastHospitalChatBotMessageJob($messageId))->handle();

        Event::assertDispatched(HospitalChatBotConsultationMessageCreated::class);
        self::assertFalse(
            (bool) HospitalChatBotConsultationMessage::query()->whereKey($messageId)->value('broadcast_pending'),
        );

        // 두 번 돌아도 다시 발행하지 않는다.
        Event::fake([HospitalChatBotConsultationMessageCreated::class]);
        (new BroadcastHospitalChatBotMessageJob($messageId))->handle();
        Event::assertNotDispatched(HospitalChatBotConsultationMessageCreated::class);
    }

    public function test_message_length_is_capped(): void
    {
        Bus::fake();

        [, $account, $consultationId] = $this->connectedConsultation();
        $this->login('hospital', $account, 'http://localhost:3002');

        $this->postJson("/api/v1/hospital/hospital-chatbot/consultations/{$consultationId}/messages", [
            'content' => str_repeat('가', HospitalChatBotConsultationMessage::MAX_LENGTH + 1),
        ])->assertStatus(422);

        $this->postJson("/api/v1/hospital/hospital-chatbot/consultations/{$consultationId}/messages", [
            'content' => str_repeat('가', HospitalChatBotConsultationMessage::MAX_LENGTH),
        ])->assertOk();
    }

    public function test_blank_content_is_rejected_before_it_takes_a_sequence(): void
    {
        Bus::fake();

        [$staff, $account, $consultationId] = $this->connectedConsultation();

        /*
         * 양쪽 경로를 다 본다. Request 클래스가 병의원·직원 각각 따로 있어서
         * 한쪽 규칙만 고쳐도 다른 쪽은 그대로 남는다.
         *
         * 여기서 지키는 것이 생각보다 미묘하다. required가 걸리는 것은 트림된
         * 값인데, 트림은 검증 규칙이 아니라 전역 TrimStrings 미들웨어가 한다.
         * Request의 content()는 검증을 통과한 뒤에야 trim()한다. 그 미들웨어가
         * 이 경로에서 빠지면 공백만 있는 메시지가 순번을 가져간다.
         */
        foreach ([
            ['hospital', $account, 'http://localhost:3002'],
            ['staff', $staff, 'http://localhost:3000'],
        ] as [$actor, $as, $origin]) {
            $this->login($actor, $as, $origin);

            foreach (['', '   ', "\n\t "] as $blank) {
                $this->postJson("/api/v1/{$actor}/hospital-chatbot/consultations/{$consultationId}/messages", [
                    'content' => $blank,
                ])->assertStatus(422);
            }
        }

        $this->login('hospital', $account, 'http://localhost:3002');

        /*
         * 순번은 상담 안에서 이어져야 한다. 빈 메시지가 번호를 먼저 가져가면
         * 클라이언트가 갭으로 읽고 없는 구간을 계속 다시 조회한다.
         */
        self::assertSame(
            1,
            (int) HospitalChatBotConsultation::query()->whereKey($consultationId)->value('last_message_sequence'),
        );

        $this->postJson("/api/v1/hospital/hospital-chatbot/consultations/{$consultationId}/messages", [
            'content' => '안녕하세요',
        ])->assertOk()->assertJsonPath('data.sequence', 2);
    }

    /** @return array{0: AccountStaff, 1: AccountHospital, 2: int} */
    private function connectedConsultation(): array
    {
        $staff = $this->staffWithPermissions([AccessPermissions::BEAULAB_CHATBOT_CONSULTATION_SHOW, AccessPermissions::BEAULAB_CHATBOT_CONSULTATION_PROCESS, AccessPermissions::BEAULAB_CHATBOT_OPERATION_SHOW, AccessPermissions::BEAULAB_CHATBOT_OPERATION_UPDATE]);
        $this->login('staff', $staff, 'http://localhost:3000');
        $this->openOperatingHours();

        $account = $this->hospitalAccount();
        $this->login('hospital', $account, 'http://localhost:3002');
        $consultationId = (int) $this->postJson('/api/v1/hospital/hospital-chatbot/consultations')
            ->assertOk()->json('data.consultation.id');

        $this->login('staff', $staff, 'http://localhost:3000');
        $this->postJson("/api/v1/staff/hospital-chatbot/consultations/{$consultationId}/claim")->assertOk();

        return [$staff, $account, $consultationId];
    }

    private function hospitalAccount(): AccountHospital
    {
        return AccountHospital::factory()
            ->forHospital(Hospital::factory()->active()->create())
            ->active()
            ->create();
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
        ])->assertOk();
    }

    private function login(string $actor, AccountStaff|AccountHospital $account, string $origin): void
    {
        $account->forceFill(['password' => 'password1234'])->save();
        $this->app['auth']->forgetGuards();
        $this->withCredentials()->withHeaders(['X-Beaulab-Client' => 'web', 'Origin' => $origin]);
        $csrf = $this->getJson("/api/v1/{$actor}/auth/csrf")->assertOk();
        if ($cookie = $csrf->getCookie("beaulab_{$actor}_session")) {
            $this->withCookie("beaulab_{$actor}_session", $cookie->getValue());
        }
        $this->withHeader('X-CSRF-TOKEN', $csrf->json('data.csrf_token'));
        $this->app['auth']->forgetGuards();

        $login = $this->postJson("/api/v1/{$actor}/auth/login", [
            'nickname' => $account->nickname,
            'password' => 'password1234',
        ])->assertOk();
        $this->withCookie("beaulab_{$actor}_session", $login->getCookie("beaulab_{$actor}_session")->getValue());
        $this->app['auth']->forgetGuards();
    }

    /** @param list<string> $permissions */
    private function staffWithPermissions(array $permissions): AccountStaff
    {
        $staff = AccountStaff::factory()->create();

        foreach ($permissions as $permission) {
            $staff->givePermissionTo(Permission::findOrCreate($permission, 'staff'));
        }

        return $staff;
    }
}
