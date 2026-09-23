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

final class HospitalChatBotConsultationFlowTest extends TestCase
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

    public function test_request_connect_and_end_walks_the_full_status_history(): void
    {
        $staff = $this->staffWithPermissions([AccessPermissions::COMMON_ACCESS]);
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

        // 종료된 뒤에는 같은 병원이 새 상담을 열 수 있다.
        $this->login('hospital', $account, 'http://localhost:3002');
        $this->postJson('/api/v1/hospital/hospital-chatbot/consultations')
            ->assertOk()
            ->assertJsonPath('data.consultation.status', HospitalChatBotConsultationStatus::WAITING);

        self::assertSame(2, HospitalChatBotConsultation::query()->count());
    }

    public function test_only_one_agent_can_claim_a_waiting_consultation(): void
    {
        $first = $this->staffWithPermissions([AccessPermissions::COMMON_ACCESS]);
        $second = $this->staffWithPermissions([AccessPermissions::COMMON_ACCESS]);

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

    public function test_user_can_cancel_while_waiting_but_not_after_an_agent_connects(): void
    {
        $staff = $this->staffWithPermissions([AccessPermissions::COMMON_ACCESS]);
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

    public function test_requesting_outside_operating_hours_is_rejected(): void
    {
        $staff = $this->staffWithPermissions([AccessPermissions::COMMON_ACCESS]);
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
        $staff = $this->staffWithPermissions([AccessPermissions::COMMON_ACCESS]);
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
        // 활성 키가 비워져야 같은 병원이 새 상담을 열 수 있다.
        self::assertNull($expired->active_hospital_id);

        $this->postJson('/api/v1/hospital/hospital-chatbot/consultations')
            ->assertOk()
            ->assertJsonPath('data.consultation.status', HospitalChatBotConsultationStatus::WAITING);
    }

    public function test_each_hospital_keeps_its_own_active_consultation(): void
    {
        $staff = $this->staffWithPermissions([AccessPermissions::COMMON_ACCESS]);
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

        // 다른 병원의 상담은 취소할 수 없다.
        $this->postJson("/api/v1/hospital/hospital-chatbot/consultations/{$firstId}/cancel")
            ->assertStatus(404);
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
