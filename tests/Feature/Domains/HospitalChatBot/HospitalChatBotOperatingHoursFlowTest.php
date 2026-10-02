<?php

declare(strict_types=1);

namespace Tests\Feature\Domains\HospitalChatBot;

use App\Common\Authorization\AccessPermissions;
use App\Domains\AccountStaff\Models\AccountStaff;
use App\Domains\HospitalChatBot\Actions\Hospital\HospitalChatBotAvailabilityForHospitalAction;
use App\Domains\HospitalChatBot\Models\HospitalChatBotClosureDate;
use App\Domains\HospitalChatBot\Models\HospitalChatBotOperatingHour;
use App\Domains\HospitalChatBot\Support\HospitalChatBotAvailabilityResolver;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\TestCase;
use Spatie\Permission\Models\Permission;

final class HospitalChatBotOperatingHoursFlowTest extends TestCase
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

    public function test_saving_replaces_closure_dates_and_allows_re_adding_a_removed_date(): void
    {
        $this->loginStaff($this->staffWithPermissions([AccessPermissions::COMMON_ACCESS]));

        $this->putJson('/api/v1/staff/hospital-chatbot/operating-hours', $this->payload([
            ['date' => '2026-01-01', 'reason' => '신정'],
            ['date' => '2026-03-01', 'reason' => '삼일절'],
        ]))
            ->assertOk()
            ->assertJsonPath('data.configured', true)
            ->assertJsonCount(2, 'data.closure_dates');

        // 하나를 빼고 저장하면 빠진 날짜는 삭제된다.
        $this->putJson('/api/v1/staff/hospital-chatbot/operating-hours', $this->payload([
            ['date' => '2026-03-01', 'reason' => '삼일절'],
        ]))
            ->assertOk()
            ->assertJsonCount(1, 'data.closure_dates')
            ->assertJsonPath('data.closure_dates.0.date', '2026-03-01');

        self::assertSame(1, HospitalChatBotClosureDate::query()->count());

        // 지웠던 날짜를 다시 등록해도 유니크 제약과 충돌하지 않는다.
        $this->putJson('/api/v1/staff/hospital-chatbot/operating-hours', $this->payload([
            ['date' => '2026-01-01', 'reason' => '신정 재등록'],
            ['date' => '2026-03-01', 'reason' => '삼일절'],
        ]))
            ->assertOk()
            ->assertJsonCount(2, 'data.closure_dates');

        self::assertSame(
            '신정 재등록',
            HospitalChatBotClosureDate::query()->where('closure_date', '2026-01-01')->value('reason'),
        );
        self::assertSame(7, HospitalChatBotOperatingHour::query()->count());

        // 휴무일을 모두 비운 저장도 허용한다.
        $this->putJson('/api/v1/staff/hospital-chatbot/operating-hours', $this->payload())
            ->assertOk()
            ->assertJsonCount(0, 'data.closure_dates');

        self::assertSame(0, HospitalChatBotClosureDate::query()->count());
    }

    public function test_closure_range_is_saved_as_one_record_and_rejects_overlap(): void
    {
        $this->loginStaff($this->staffWithPermissions([AccessPermissions::COMMON_ACCESS]));
        $payload = $this->payload();
        $payload['closure_dates'] = [['date' => '2026-10-15', 'end_date' => '2026-10-16', 'reason' => '휴무']];
        $this->putJson('/api/v1/staff/hospital-chatbot/operating-hours', $payload)->assertOk()
            ->assertJsonCount(1, 'data.closure_dates')->assertJsonPath('data.closure_dates.0.end_date', '2026-10-16');
        self::assertSame(1, HospitalChatBotClosureDate::query()->count());
        $payload['closure_dates'][] = ['date' => '2026-10-16', 'end_date' => '2026-10-17'];
        $this->putJson('/api/v1/staff/hospital-chatbot/operating-hours', $payload)->assertStatus(422);
        self::assertSame(1, HospitalChatBotClosureDate::query()->count());
        $payload['closure_dates'] = [];
        $this->putJson('/api/v1/staff/hospital-chatbot/operating-hours', $payload)->assertOk();
        self::assertSame(0, HospitalChatBotClosureDate::query()->count());
    }

    public function test_saving_lunch_and_public_holidays_and_rejecting_invalid_values(): void
    {
        $this->loginStaff($this->staffWithPermissions([AccessPermissions::COMMON_ACCESS]));
        $payload = $this->payload();
        $payload['public_holiday_codes'] = ['CHUSEOK', 'HANGUL_DAY'];
        $payload['break_starts_at'] = '12:00';
        $payload['break_ends_at'] = '13:00';
        $payload['weekly_schedule'][1]['closes_at'] = '12:00';
        $this->putJson('/api/v1/staff/hospital-chatbot/operating-hours', $payload)->assertOk()
            ->assertJsonPath('data.break_starts_at', '12:00')
            ->assertJsonPath('data.public_holiday_codes', ['CHUSEOK', 'HANGUL_DAY']);
        $this->putJson('/api/v1/staff/hospital-chatbot/operating-hours', $this->payload())->assertOk()
            ->assertJsonPath('data.break_starts_at', '12:00');
        $legacyPayload = $payload;
        $legacyPayload['weekly_schedule'][0]['break_starts_at'] = '12:00';
        $this->putJson('/api/v1/staff/hospital-chatbot/operating-hours', $legacyPayload)->assertStatus(422);
        $payload['break_ends_at'] = '11:00';
        $this->putJson('/api/v1/staff/hospital-chatbot/operating-hours', $payload)->assertStatus(422);
        $payload['break_ends_at'] = null;
        $this->putJson('/api/v1/staff/hospital-chatbot/operating-hours', $payload)->assertStatus(422);
        $payload['break_ends_at'] = '13:00';
        $payload['public_holiday_codes'] = ['UNKNOWN'];
        $this->putJson('/api/v1/staff/hospital-chatbot/operating-hours', $payload)->assertStatus(422);
        $payload['public_holiday_codes'] = [];
        $payload['break_starts_at'] = null;
        $payload['break_ends_at'] = null;
        $this->putJson('/api/v1/staff/hospital-chatbot/operating-hours', $payload)->assertOk()
            ->assertJsonPath('data.break_starts_at', null)
            ->assertJsonPath('data.break_ends_at', null);
    }

    public function test_saving_rejects_an_open_day_without_a_valid_time_range(): void
    {
        $this->loginStaff($this->staffWithPermissions([AccessPermissions::COMMON_ACCESS]));

        $payload = $this->payload();
        $payload['weekly_schedule'][0]['opens_at'] = '18:00';
        $payload['weekly_schedule'][0]['closes_at'] = '09:00';
        $payload['weekly_schedule'][1]['opens_at'] = null;

        $response = $this->putJson('/api/v1/staff/hospital-chatbot/operating-hours', $payload)
            ->assertStatus(422)
            ->assertJsonPath('success', false);

        $errors = (array) $response->json('error.details.errors');
        self::assertArrayHasKey('weekly_schedule.0.closes_at', $errors);
        self::assertArrayHasKey('weekly_schedule.1.opens_at', $errors);

        self::assertSame(0, HospitalChatBotOperatingHour::query()->count());
    }

    public function test_schedule_without_any_open_day_reports_no_next_open_at(): void
    {
        $this->loginStaff($this->staffWithPermissions([AccessPermissions::COMMON_ACCESS]));

        $payload = $this->payload();
        $payload['weekly_schedule'] = array_map(
            static fn (array $day): array => ['day_of_week' => $day['day_of_week'], 'is_open' => false, 'opens_at' => null, 'closes_at' => null],
            $payload['weekly_schedule'],
        );

        $this->putJson('/api/v1/staff/hospital-chatbot/operating-hours', $payload)->assertOk();

        $availability = app(HospitalChatBotAvailabilityForHospitalAction::class)->execute();

        self::assertFalse($availability['is_available']);
        self::assertNull($availability['next_open_at']);
    }

    public function test_force_close_switch_overrides_the_weekly_schedule(): void
    {
        $this->loginStaff($this->staffWithPermissions([AccessPermissions::COMMON_ACCESS]));

        $payload = $this->payload();
        $payload['is_force_closed'] = true;
        $payload['force_closed_reason'] = '시스템 점검';

        $this->putJson('/api/v1/staff/hospital-chatbot/operating-hours', $payload)
            ->assertOk()
            ->assertJsonPath('data.is_force_closed', true)
            ->assertJsonPath('data.force_closed_reason', '시스템 점검');

        $availability = app(HospitalChatBotAvailabilityForHospitalAction::class)->execute();

        self::assertFalse($availability['is_available']);
        self::assertSame(HospitalChatBotAvailabilityResolver::REASON_FORCE_CLOSED, $availability['reason']);
        self::assertNull($availability['next_open_at']);

        // 스위치를 내리면 요일 설정으로 돌아간다.
        $payload['is_force_closed'] = false;
        $this->putJson('/api/v1/staff/hospital-chatbot/operating-hours', $payload)
            ->assertOk()
            ->assertJsonPath('data.is_force_closed', false)
            ->assertJsonPath('data.force_closed_reason', null);

        self::assertNotSame(
            HospitalChatBotAvailabilityResolver::REASON_FORCE_CLOSED,
            app(HospitalChatBotAvailabilityForHospitalAction::class)->execute()['reason'],
        );
    }

    public function test_message_settings_save_independently_and_only_welcome_is_exposed_to_hospital(): void
    {
        $this->loginStaff($this->staffWithPermissions([AccessPermissions::COMMON_ACCESS]));
        $endpoint = '/api/v1/staff/hospital-chatbot/message-settings';
        $this->getJson($endpoint)->assertOk()->assertJsonPath('data.macros.0.shortcut', '확인');
        $values = ['welcome_message' => '병의원 상담에 오신 것을 환영합니다.', 'connection_message' => '{상담원명} 상담사입니다.', 'macros' => [['shortcut' => '안내', 'content' => '안내해 드리겠습니다.']]];
        $this->putJson($endpoint, $values)->assertOk()->assertJsonPath('data.macros.0.shortcut', '안내');
        $this->getJson($endpoint)->assertOk()->assertJsonPath('data.connection_message', '{상담원명} 상담사입니다.');
        $availability = app(HospitalChatBotAvailabilityForHospitalAction::class)->execute();
        self::assertSame($values['welcome_message'], $availability['welcome_message']);
        self::assertArrayNotHasKey('macros', $availability);
        self::assertArrayNotHasKey('connection_message', $availability);
        self::assertSame(0, HospitalChatBotOperatingHour::query()->count());
        $values['macros'] = [];
        $this->putJson($endpoint, $values)->assertOk()->assertJsonCount(0, 'data.macros');
        self::assertSame(1, \App\Domains\HospitalChatBot\Models\HospitalChatBotMessageSetting::query()->count());
    }

    public function test_invalid_macros_do_not_replace_saved_settings(): void
    {
        $this->loginStaff($this->staffWithPermissions([AccessPermissions::COMMON_ACCESS]));
        $values = \App\Domains\HospitalChatBot\Models\HospitalChatBotMessageSetting::defaults();
        $values['macros'] = [['shortcut' => '확인', 'content' => '확인합니다.'], ['shortcut' => '확인', 'content' => '중복입니다.']];
        $this->putJson('/api/v1/staff/hospital-chatbot/message-settings', $values)->assertStatus(422);
        $values['macros'] = [['shortcut' => '#금지 공백', 'content' => '']];
        $this->putJson('/api/v1/staff/hospital-chatbot/message-settings', $values)->assertStatus(422);
        $values['macros'] = [];
        self::assertSame(0, \App\Domains\HospitalChatBot\Models\HospitalChatBotMessageSetting::query()->count());
        $values['connection_message'] = '{상담원명}{상담원명}';
        $this->putJson('/api/v1/staff/hospital-chatbot/message-settings', $values)->assertOk()
            ->assertJsonPath('data.connection_message', '{상담원명}{상담원명}');
    }

    public function test_partial_message_settings_preserve_the_other_menu_and_allow_clearing_macros(): void
    {
        $this->loginStaff($this->staffWithPermissions([AccessPermissions::COMMON_ACCESS]));
        $endpoint = '/api/v1/staff/hospital-chatbot/message-settings';
        $this->putJson($endpoint, ['welcome_message' => '새 안내', 'connection_message' => '새 연결 인사'])
            ->assertOk()->assertJsonPath('data.macros.0.shortcut', '확인');
        $this->putJson($endpoint, ['macros' => [['shortcut' => '안내', 'content' => '새 답변']]])
            ->assertOk()->assertJsonPath('data.welcome_message', '새 안내')->assertJsonPath('data.connection_message', '새 연결 인사');
        $this->putJson($endpoint, ['macros' => []])->assertOk()->assertJsonCount(0, 'data.macros')
            ->assertJsonPath('data.welcome_message', '새 안내');
        $this->putJson($endpoint, [])->assertStatus(422);
        $this->putJson($endpoint, ['welcome_message' => '미완성', 'macros' => []])->assertStatus(422);
    }

    /** @param list<array{date:string,reason:?string}> $closureDates */
    private function payload(array $closureDates = []): array
    {
        return [
            'weekly_schedule' => array_map(static fn (int $day): array => [
                'day_of_week' => $day,
                'is_open' => $day <= 5,
                'opens_at' => $day <= 5 ? '09:00' : null,
                'closes_at' => $day <= 5 ? '18:00' : null,
            ], range(1, 7)),
            'closure_dates' => $closureDates,
        ];
    }

    private function startWebSession(string $actor, string $origin): void
    {
        $this->app['auth']->forgetGuards();
        $this->withCredentials()->withHeaders([
            'X-Beaulab-Client' => 'web',
            'Origin' => $origin,
        ]);
        $response = $this->getJson("/api/v1/{$actor}/auth/csrf")->assertOk();
        $this->withCookie("beaulab_{$actor}_session", $response->getCookie("beaulab_{$actor}_session")->getValue());
        $this->withHeader('X-CSRF-TOKEN', $response->json('data.csrf_token'));
        $this->app['auth']->forgetGuards();
    }

    private function loginStaff(AccountStaff $staff): void
    {
        $staff->forceFill(['password' => 'password1234'])->save();
        $this->startWebSession('staff', 'http://localhost:3000');
        $response = $this->postJson('/api/v1/staff/auth/login', [
            'nickname' => $staff->nickname,
            'password' => 'password1234',
        ])->assertOk();
        $this->withCookie('beaulab_staff_session', $response->getCookie('beaulab_staff_session')->getValue());
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
