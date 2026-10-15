<?php

declare(strict_types=1);

namespace Tests\Feature\Domains\HospitalChatBot;

use App\Common\Authorization\AccessPermissions;
use App\Domains\AccountHospital\Models\AccountHospital;
use App\Domains\AccountStaff\Models\AccountStaff;
use App\Domains\Hospital\Models\Hospital;
use App\Domains\HospitalChatBot\Models\HospitalChatBotConsultation;
use App\Domains\HospitalChatBot\Models\HospitalChatBotQna;
use App\Domains\HospitalChatBot\Support\HospitalChatBotConsultationStatus;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\TestCase;
use Spatie\Permission\Models\Permission;

final class HospitalChatBotConsultationFlowTest extends TestCase
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

    public function test_request_connect_and_end_walks_the_full_status_history(): void
    {
        $staff = $this->staffWithPermissions([AccessPermissions::BEAULAB_CHATBOT_CONSULTATION_SHOW, AccessPermissions::BEAULAB_CHATBOT_CONSULTATION_PROCESS, AccessPermissions::BEAULAB_CHATBOT_OPERATION_SHOW, AccessPermissions::BEAULAB_CHATBOT_OPERATION_UPDATE]);
        $this->login('staff', $staff, 'http://localhost:3000');
        $this->openOperatingHours();

        $account = $this->hospitalAccount();
        $this->login('hospital', $account, 'http://localhost:3002');

        $created = $this->postJson('/api/v1/hospital/hospital-chatbot/consultations')
            ->assertOk()
            ->assertJsonPath('data.consultation.status', HospitalChatBotConsultationStatus::WAITING);
        $consultationId = (int) $created->json('data.consultation.id');

        // 연타해도 상담은 한 건만 생긴다.
        $this->postJson('/api/v1/hospital/hospital-chatbot/consultations')
            ->assertOk()
            ->assertJsonPath('data.consultation.id', $consultationId);
        self::assertSame(1, HospitalChatBotConsultation::query()->count());

        // 새로고침 복구용 활성 조회.
        $this->getJson('/api/v1/hospital/hospital-chatbot/consultations/active')
            ->assertOk()
            ->assertJsonPath('data.consultation.id', $consultationId);

        $this->login('staff', $staff, 'http://localhost:3000');

        $this->getJson('/api/v1/staff/hospital-chatbot/consultations')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $consultationId);

        $this->postJson("/api/v1/staff/hospital-chatbot/consultations/{$consultationId}/claim")
            ->assertOk()
            ->assertJsonPath('data.status', HospitalChatBotConsultationStatus::CONNECTED)
            ->assertJsonPath('data.assigned_staff.id', (int) $staff->id);

        $this->postJson("/api/v1/staff/hospital-chatbot/consultations/{$consultationId}/end")
            ->assertOk()
            ->assertJsonPath('data.status', HospitalChatBotConsultationStatus::ENDED)
            ->assertJsonPath('data.close_reason', HospitalChatBotConsultationStatus::REASON_AGENT_COMPLETED);

        // 종료를 다시 눌러도 같은 결과를 돌려준다.
        $this->postJson("/api/v1/staff/hospital-chatbot/consultations/{$consultationId}/end")
            ->assertOk()
            ->assertJsonPath('data.status', HospitalChatBotConsultationStatus::ENDED);

        $detail = $this->getJson("/api/v1/staff/hospital-chatbot/consultations/{$consultationId}")
            ->assertOk()
            ->json('data.status_histories');

        self::assertSame(
            [[null, 'WAITING'], ['WAITING', 'CONNECTED'], ['CONNECTED', 'ENDED']],
            array_map(static fn (array $row): array => [$row['from_status'], $row['to_status']], $detail),
        );

        // 종료된 뒤에는 같은 병의원이 새 상담을 열 수 있다.
        $this->login('hospital', $account, 'http://localhost:3002');
        $this->postJson('/api/v1/hospital/hospital-chatbot/consultations')
            ->assertOk()
            ->assertJsonPath('data.consultation.status', HospitalChatBotConsultationStatus::WAITING);

        self::assertSame(2, HospitalChatBotConsultation::query()->count());
    }

    public function test_only_one_agent_can_claim_a_waiting_consultation(): void
    {
        $first = $this->staffWithPermissions([AccessPermissions::BEAULAB_CHATBOT_CONSULTATION_SHOW, AccessPermissions::BEAULAB_CHATBOT_CONSULTATION_PROCESS, AccessPermissions::BEAULAB_CHATBOT_OPERATION_SHOW, AccessPermissions::BEAULAB_CHATBOT_OPERATION_UPDATE]);
        $second = $this->staffWithPermissions([AccessPermissions::BEAULAB_CHATBOT_CONSULTATION_SHOW, AccessPermissions::BEAULAB_CHATBOT_CONSULTATION_PROCESS, AccessPermissions::BEAULAB_CHATBOT_OPERATION_SHOW, AccessPermissions::BEAULAB_CHATBOT_OPERATION_UPDATE]);

        $this->login('staff', $first, 'http://localhost:3000');
        $this->openOperatingHours();

        $account = $this->hospitalAccount();
        $this->login('hospital', $account, 'http://localhost:3002');
        $consultationId = (int) $this->postJson('/api/v1/hospital/hospital-chatbot/consultations')
            ->assertOk()->json('data.consultation.id');

        $this->login('staff', $first, 'http://localhost:3000');
        $this->postJson("/api/v1/staff/hospital-chatbot/consultations/{$consultationId}/claim")->assertOk();

        // 같은 상담원이 다시 눌러도 성공으로 취급한다.
        $this->postJson("/api/v1/staff/hospital-chatbot/consultations/{$consultationId}/claim")
            ->assertOk()
            ->assertJsonPath('data.assigned_staff.id', (int) $first->id);

        $this->login('staff', $second, 'http://localhost:3000');
        $this->postJson("/api/v1/staff/hospital-chatbot/consultations/{$consultationId}/claim")
            ->assertStatus(409)
            ->assertJsonPath('error.code', 'CONSULTATION_ALREADY_CLAIMED');

        // 담당이 아닌 상담원은 종료할 수 없다.
        $this->postJson("/api/v1/staff/hospital-chatbot/consultations/{$consultationId}/end")
            ->assertStatus(403);

        self::assertSame(
            (int) $first->id,
            (int) HospitalChatBotConsultation::query()->whereKey($consultationId)->value('assigned_staff_id'),
        );
    }

    public function test_ending_checks_the_assignment_inside_the_update_not_on_a_stale_read(): void
    {
        $owner = $this->staffWithPermissions([AccessPermissions::BEAULAB_CHATBOT_CONSULTATION_SHOW, AccessPermissions::BEAULAB_CHATBOT_CONSULTATION_PROCESS, AccessPermissions::BEAULAB_CHATBOT_OPERATION_SHOW, AccessPermissions::BEAULAB_CHATBOT_OPERATION_UPDATE]);
        $other = $this->staffWithPermissions([AccessPermissions::BEAULAB_CHATBOT_CONSULTATION_SHOW, AccessPermissions::BEAULAB_CHATBOT_CONSULTATION_PROCESS, AccessPermissions::BEAULAB_CHATBOT_OPERATION_SHOW, AccessPermissions::BEAULAB_CHATBOT_OPERATION_UPDATE]);

        $this->login('staff', $owner, 'http://localhost:3000');
        $this->openOperatingHours();

        $account = $this->hospitalAccount();
        $this->login('hospital', $account, 'http://localhost:3002');
        $consultationId = (int) $this->postJson('/api/v1/hospital/hospital-chatbot/consultations')
            ->assertOk()->json('data.consultation.id');

        $this->login('staff', $owner, 'http://localhost:3000');
        $this->postJson("/api/v1/staff/hospital-chatbot/consultations/{$consultationId}/claim")->assertOk();

        // 담당이 아닌 상담원의 종료는 조건부 UPDATE 단계에서 막힌다.
        $this->login('staff', $other, 'http://localhost:3000');
        $this->postJson("/api/v1/staff/hospital-chatbot/consultations/{$consultationId}/end")
            ->assertStatus(403);

        self::assertSame(
            HospitalChatBotConsultationStatus::CONNECTED,
            HospitalChatBotConsultation::query()->whereKey($consultationId)->value('status'),
        );

        // 담당 상담원은 그대로 종료할 수 있다.
        $this->login('staff', $owner, 'http://localhost:3000');
        $this->postJson("/api/v1/staff/hospital-chatbot/consultations/{$consultationId}/end")
            ->assertOk()
            ->assertJsonPath('data.status', HospitalChatBotConsultationStatus::ENDED);

        // 종료된 뒤에도 남의 상담을 건드릴 수 없다.
        $this->login('staff', $other, 'http://localhost:3000');
        $this->postJson("/api/v1/staff/hospital-chatbot/consultations/{$consultationId}/end")
            ->assertStatus(403);
    }

    public function test_an_empty_status_filter_still_hides_finished_consultations(): void
    {
        $staff = $this->staffWithPermissions([AccessPermissions::BEAULAB_CHATBOT_CONSULTATION_SHOW, AccessPermissions::BEAULAB_CHATBOT_CONSULTATION_PROCESS, AccessPermissions::BEAULAB_CHATBOT_OPERATION_SHOW, AccessPermissions::BEAULAB_CHATBOT_OPERATION_UPDATE]);
        $this->login('staff', $staff, 'http://localhost:3000');
        $this->openOperatingHours();

        $account = $this->hospitalAccount();
        $this->login('hospital', $account, 'http://localhost:3002');
        $canceledId = (int) $this->postJson('/api/v1/hospital/hospital-chatbot/consultations')
            ->assertOk()->json('data.consultation.id');
        $this->postJson("/api/v1/hospital/hospital-chatbot/consultations/{$canceledId}/cancel")->assertOk();

        $waitingId = (int) $this->postJson('/api/v1/hospital/hospital-chatbot/consultations')
            ->assertOk()->json('data.consultation.id');

        $this->login('staff', $staff, 'http://localhost:3000');

        // `?status=` 는 필터를 주지 않은 것과 같아야 한다. 빈 배열을 그대로 넘기면 취소 건까지 나온다.
        $this->getJson('/api/v1/staff/hospital-chatbot/consultations?status=')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $waitingId);

        // 명시적으로 요청하면 취소 건도 볼 수 있다.
        $this->getJson('/api/v1/staff/hospital-chatbot/consultations?status=CANCELED')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $canceledId);
    }

    public function test_user_can_cancel_while_waiting_but_not_after_an_agent_connects(): void
    {
        $staff = $this->staffWithPermissions([AccessPermissions::BEAULAB_CHATBOT_CONSULTATION_SHOW, AccessPermissions::BEAULAB_CHATBOT_CONSULTATION_PROCESS, AccessPermissions::BEAULAB_CHATBOT_OPERATION_SHOW, AccessPermissions::BEAULAB_CHATBOT_OPERATION_UPDATE]);
        $this->login('staff', $staff, 'http://localhost:3000');
        $this->openOperatingHours();

        $account = $this->hospitalAccount();
        $this->login('hospital', $account, 'http://localhost:3002');
        $consultationId = (int) $this->postJson('/api/v1/hospital/hospital-chatbot/consultations')
            ->assertOk()->json('data.consultation.id');

        $this->postJson("/api/v1/hospital/hospital-chatbot/consultations/{$consultationId}/cancel")
            ->assertOk()
            ->assertJsonPath('data.consultation.status', HospitalChatBotConsultationStatus::CANCELED)
            ->assertJsonPath('data.consultation.close_reason', HospitalChatBotConsultationStatus::REASON_USER_CANCELED);

        // 취소된 상담은 활성 조회에서 사라진다.
        $this->getJson('/api/v1/hospital/hospital-chatbot/consultations/active')
            ->assertOk()
            ->assertJsonPath('data.consultation', null);

        $secondId = (int) $this->postJson('/api/v1/hospital/hospital-chatbot/consultations')
            ->assertOk()->json('data.consultation.id');

        $this->login('staff', $staff, 'http://localhost:3000');
        $this->postJson("/api/v1/staff/hospital-chatbot/consultations/{$secondId}/claim")->assertOk();

        $this->login('hospital', $account, 'http://localhost:3002');
        $this->postJson("/api/v1/hospital/hospital-chatbot/consultations/{$secondId}/cancel")
            ->assertStatus(409)
            ->assertJsonPath('error.code', 'CONSULTATION_ALREADY_CLAIMED');
    }

    public function test_hospital_user_can_end_their_own_connected_consultation(): void
    {
        $staff = $this->staffWithPermissions([AccessPermissions::BEAULAB_CHATBOT_CONSULTATION_SHOW, AccessPermissions::BEAULAB_CHATBOT_CONSULTATION_PROCESS, AccessPermissions::BEAULAB_CHATBOT_OPERATION_SHOW, AccessPermissions::BEAULAB_CHATBOT_OPERATION_UPDATE]);
        $this->login('staff', $staff, 'http://localhost:3000');
        $this->openOperatingHours();

        $account = $this->hospitalAccount();
        $this->login('hospital', $account, 'http://localhost:3002');
        $consultationId = (int) $this->postJson('/api/v1/hospital/hospital-chatbot/consultations')
            ->assertOk()->json('data.consultation.id');

        // 대기 중에는 종료가 아니라 취소를 써야 한다.
        $this->postJson("/api/v1/hospital/hospital-chatbot/consultations/{$consultationId}/end")
            ->assertStatus(409)
            ->assertJsonPath('error.code', 'CONSULTATION_NOT_ACTIVE');

        $this->login('staff', $staff, 'http://localhost:3000');
        $this->postJson("/api/v1/staff/hospital-chatbot/consultations/{$consultationId}/claim")->assertOk();

        $this->login('hospital', $account, 'http://localhost:3002');
        $this->postJson("/api/v1/hospital/hospital-chatbot/consultations/{$consultationId}/end")
            ->assertOk()
            ->assertJsonPath('data.consultation.status', HospitalChatBotConsultationStatus::ENDED)
            ->assertJsonPath('data.consultation.close_reason', HospitalChatBotConsultationStatus::REASON_USER_ENDED);

        // 다시 눌러도 같은 결과를 돌려준다.
        $this->postJson("/api/v1/hospital/hospital-chatbot/consultations/{$consultationId}/end")
            ->assertOk()
            ->assertJsonPath('data.consultation.status', HospitalChatBotConsultationStatus::ENDED);

        // 활성 상담이 비었으므로 새 상담을 열 수 있다.
        $this->postJson('/api/v1/hospital/hospital-chatbot/consultations')
            ->assertOk()
            ->assertJsonPath('data.consultation.status', HospitalChatBotConsultationStatus::WAITING);

        $ended = HospitalChatBotConsultation::query()->whereKey($consultationId)->firstOrFail();
        self::assertNull($ended->active_hospital_id);

        $this->login('staff', $staff, 'http://localhost:3000');
        $histories = $this->getJson("/api/v1/staff/hospital-chatbot/consultations/{$consultationId}")
            ->assertOk()->json('data.status_histories');

        // 마지막 전이의 주체가 병의원 사용자로 남아야 상담원 종료와 구분된다.
        $last = end($histories);
        self::assertSame(HospitalChatBotConsultationStatus::ACTOR_HOSPITAL_USER, $last['actor_type']);
        self::assertSame(HospitalChatBotConsultationStatus::REASON_USER_ENDED, $last['reason']);
    }

    public function test_hospital_user_cannot_end_another_hospitals_consultation(): void
    {
        $staff = $this->staffWithPermissions([AccessPermissions::BEAULAB_CHATBOT_CONSULTATION_SHOW, AccessPermissions::BEAULAB_CHATBOT_CONSULTATION_PROCESS, AccessPermissions::BEAULAB_CHATBOT_OPERATION_SHOW, AccessPermissions::BEAULAB_CHATBOT_OPERATION_UPDATE]);
        $this->login('staff', $staff, 'http://localhost:3000');
        $this->openOperatingHours();

        $owner = $this->hospitalAccount();
        $other = $this->hospitalAccount();

        $this->login('hospital', $owner, 'http://localhost:3002');
        $consultationId = (int) $this->postJson('/api/v1/hospital/hospital-chatbot/consultations')
            ->assertOk()->json('data.consultation.id');

        $this->login('staff', $staff, 'http://localhost:3000');
        $this->postJson("/api/v1/staff/hospital-chatbot/consultations/{$consultationId}/claim")->assertOk();

        $this->login('hospital', $other, 'http://localhost:3002');
        $this->postJson("/api/v1/hospital/hospital-chatbot/consultations/{$consultationId}/end")
            ->assertStatus(404);

        self::assertSame(
            HospitalChatBotConsultationStatus::CONNECTED,
            HospitalChatBotConsultation::query()->whereKey($consultationId)->value('status'),
        );
    }

    public function test_requesting_outside_operating_hours_is_rejected(): void
    {
        $staff = $this->staffWithPermissions([AccessPermissions::BEAULAB_CHATBOT_CONSULTATION_SHOW, AccessPermissions::BEAULAB_CHATBOT_CONSULTATION_PROCESS, AccessPermissions::BEAULAB_CHATBOT_OPERATION_SHOW, AccessPermissions::BEAULAB_CHATBOT_OPERATION_UPDATE]);
        $this->login('staff', $staff, 'http://localhost:3000');
        $this->openOperatingHours(open: false);

        $account = $this->hospitalAccount();
        $this->login('hospital', $account, 'http://localhost:3002');

        $this->postJson('/api/v1/hospital/hospital-chatbot/consultations')
            ->assertStatus(422)
            ->assertJsonPath('error.code', 'CONSULTATION_UNAVAILABLE');

        self::assertSame(0, HospitalChatBotConsultation::query()->count());
    }

    public function test_waiting_consultations_expire_after_the_configured_timeout(): void
    {
        $staff = $this->staffWithPermissions([AccessPermissions::BEAULAB_CHATBOT_CONSULTATION_SHOW, AccessPermissions::BEAULAB_CHATBOT_CONSULTATION_PROCESS, AccessPermissions::BEAULAB_CHATBOT_OPERATION_SHOW, AccessPermissions::BEAULAB_CHATBOT_OPERATION_UPDATE]);
        $this->login('staff', $staff, 'http://localhost:3000');
        $this->openOperatingHours();

        $account = $this->hospitalAccount();
        $this->login('hospital', $account, 'http://localhost:3002');
        $consultationId = (int) $this->postJson('/api/v1/hospital/hospital-chatbot/consultations')
            ->assertOk()->json('data.consultation.id');

        // 아직 만료 시간이 되지 않았으면 그대로 둔다.
        $this->artisan('hospital-chatbot:expire-waiting-consultations')->assertExitCode(0);
        self::assertSame(
            HospitalChatBotConsultationStatus::WAITING,
            HospitalChatBotConsultation::query()->whereKey($consultationId)->value('status'),
        );

        HospitalChatBotConsultation::query()->whereKey($consultationId)->update([
            'requested_at' => now()->subMinutes((int) config('hospital_chatbot.waiting_timeout_minutes') + 10),
        ]);

        $this->artisan('hospital-chatbot:expire-waiting-consultations')->assertExitCode(0);

        $expired = HospitalChatBotConsultation::query()->whereKey($consultationId)->firstOrFail();
        self::assertSame(HospitalChatBotConsultationStatus::CANCELED, $expired->status);
        self::assertSame(HospitalChatBotConsultationStatus::REASON_WAITING_TIMEOUT, $expired->close_reason);
        // 활성 키가 비워져야 같은 병의원이 새 상담을 열 수 있다.
        self::assertNull($expired->active_hospital_id);

        $this->postJson('/api/v1/hospital/hospital-chatbot/consultations')
            ->assertOk()
            ->assertJsonPath('data.consultation.status', HospitalChatBotConsultationStatus::WAITING);
    }

    public function test_each_hospital_keeps_its_own_active_consultation(): void
    {
        $staff = $this->staffWithPermissions([AccessPermissions::BEAULAB_CHATBOT_CONSULTATION_SHOW, AccessPermissions::BEAULAB_CHATBOT_CONSULTATION_PROCESS, AccessPermissions::BEAULAB_CHATBOT_OPERATION_SHOW, AccessPermissions::BEAULAB_CHATBOT_OPERATION_UPDATE]);
        $this->login('staff', $staff, 'http://localhost:3000');
        $this->openOperatingHours();

        $first = $this->hospitalAccount();
        $second = $this->hospitalAccount();

        $this->login('hospital', $first, 'http://localhost:3002');
        $firstId = (int) $this->postJson('/api/v1/hospital/hospital-chatbot/consultations')
            ->assertOk()->json('data.consultation.id');

        $this->login('hospital', $second, 'http://localhost:3002');
        $secondId = (int) $this->postJson('/api/v1/hospital/hospital-chatbot/consultations')
            ->assertOk()->json('data.consultation.id');

        self::assertNotSame($firstId, $secondId);

        // 다른 병의원의 상담은 취소할 수 없다.
        $this->postJson("/api/v1/hospital/hospital-chatbot/consultations/{$firstId}/cancel")
            ->assertStatus(404);
    }

    public function test_the_qna_the_user_was_reading_reaches_the_agent_and_survives_edits(): void
    {
        $staff = $this->staffWithPermissions([AccessPermissions::BEAULAB_CHATBOT_CONSULTATION_SHOW, AccessPermissions::BEAULAB_CHATBOT_CONSULTATION_PROCESS, AccessPermissions::BEAULAB_CHATBOT_OPERATION_SHOW, AccessPermissions::BEAULAB_CHATBOT_OPERATION_UPDATE]);
        $this->login('staff', $staff, 'http://localhost:3000');
        $this->openOperatingHours();

        $qna = HospitalChatBotQna::query()->create([
            'category' => '병의원회원',
            'question' => '가입 승인은 얼마나 걸리나요?',
            'question_key' => HospitalChatBotQna::questionKey('가입 승인은 얼마나 걸리나요?'),
            'answer' => '영업일 기준 1~2일 걸립니다.',
        ]);

        $account = $this->hospitalAccount();
        $this->login('hospital', $account, 'http://localhost:3002');

        $consultationId = (int) $this->postJson('/api/v1/hospital/hospital-chatbot/consultations', [
            'context_qna_id' => (int) $qna->id,
        ])->assertOk()
            ->assertJsonPath('data.consultation.context_qna.question', '가입 승인은 얼마나 걸리나요?')
            ->json('data.consultation.id');

        // 상담을 연 뒤 Q&A가 고쳐져도 상담원이 보는 것은 요청자가 읽던 그 글이어야 한다.
        $qna->forceFill(['question' => '완전히 다른 질문', 'answer' => '완전히 다른 답변'])->save();

        $this->login('staff', $staff, 'http://localhost:3000');
        $this->getJson("/api/v1/staff/hospital-chatbot/consultations/{$consultationId}")
            ->assertOk()
            ->assertJsonPath('data.context_qna.id', (int) $qna->id)
            ->assertJsonPath('data.context_qna.question', '가입 승인은 얼마나 걸리나요?')
            ->assertJsonPath('data.context_qna.answer', '영업일 기준 1~2일 걸립니다.');

        // 대기 목록에서도 무엇을 보다 불렀는지 바로 보인다.
        $this->getJson('/api/v1/staff/hospital-chatbot/consultations')
            ->assertOk()
            ->assertJsonPath('data.0.context_qna.question', '가입 승인은 얼마나 걸리나요?');
    }

    public function test_a_consultation_opens_even_when_the_context_qna_is_missing(): void
    {
        $staff = $this->staffWithPermissions([AccessPermissions::BEAULAB_CHATBOT_CONSULTATION_SHOW, AccessPermissions::BEAULAB_CHATBOT_CONSULTATION_PROCESS, AccessPermissions::BEAULAB_CHATBOT_OPERATION_SHOW, AccessPermissions::BEAULAB_CHATBOT_OPERATION_UPDATE]);
        $this->login('staff', $staff, 'http://localhost:3000');
        $this->openOperatingHours();

        $account = $this->hospitalAccount();
        $this->login('hospital', $account, 'http://localhost:3002');

        // 맥락은 곁다리다. 없는 Q&A를 보내도 상담 요청 자체가 막히면 안 된다.
        $this->postJson('/api/v1/hospital/hospital-chatbot/consultations', ['context_qna_id' => 999999])
            ->assertOk()
            ->assertJsonPath('data.consultation.status', HospitalChatBotConsultationStatus::WAITING)
            ->assertJsonPath('data.consultation.context_qna', null);
    }

    private function hospitalAccount(): AccountHospital
    {
        return AccountHospital::factory()
            ->forHospital(Hospital::factory()->active()->create())
            ->active()
            ->create();
    }

    private function openOperatingHours(bool $open = true): void
    {
        $this->putJson('/api/v1/staff/hospital-chatbot/operating-hours', [
            'weekly_schedule' => array_map(static fn (int $day): array => [
                'day_of_week' => $day,
                'is_open' => $open,
                'opens_at' => $open ? '00:00' : null,
                'closes_at' => $open ? '23:59' : null,
            ], range(1, 7)),
            'closure_dates' => [],
        ])->assertOk();
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
