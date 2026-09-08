<?php

declare(strict_types=1);

namespace Tests\Feature\Domains\Hospital;

use App\Common\Authorization\AccessPermissions;
use App\Domains\AccountHospital\Models\AccountHospital;
use App\Domains\AccountStaff\Models\AccountStaff;
use App\Domains\Hospital\Models\Hospital;
use App\Domains\Hospital\Models\HospitalStatusChangeRequest;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\TestCase;
use Illuminate\Support\Facades\Queue;
use Spatie\Permission\Models\Permission;

final class HospitalStatusChangeRequestFlowTest extends TestCase
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

    public function test_approved_operation_stop_hides_hospital_without_blocking_hospital_account_login(): void
    {
        Queue::fake();

        $hospital = Hospital::factory()->active()->create();
        $accountHospital = AccountHospital::factory()
            ->forHospital($hospital)
            ->withNickname('operation_stop_login_test')
            ->withPassword('password1234')
            ->active()
            ->create();

        $requester = $this->staffWithPermissions([
            AccessPermissions::COMMON_ACCESS,
            AccessPermissions::BEAULAB_HOSPITAL_STATUS_REQUEST_CREATE,
        ]);

        $this->loginStaff($requester);

        $this->patchJson("/api/v1/staff/hospitals/{$hospital->id}/status", [
            'status' => Hospital::STATUS_SUSPENDED,
            'reason' => '직접 처리 시도',
        ])->assertForbidden();

        $createResponse = $this->postJson("/api/v1/staff/hospitals/{$hospital->id}/status-change-requests", [
            'target_status' => Hospital::STATUS_SUSPENDED,
            'reason' => '운영 정책 위반 확인 요청',
        ]);

        $createResponse
            ->assertOk()
            ->assertJsonPath('data.request.status', HospitalStatusChangeRequest::STATUS_PENDING);

        self::assertSame(Hospital::STATUS_ACTIVE, $hospital->refresh()->status);
        self::assertSame(AccountHospital::STATUS_ACTIVE, $accountHospital->refresh()->status);

        $statusChangeRequestId = (int) $createResponse->json('data.request.id');
        $approver = $this->staffWithPermissions([
            AccessPermissions::COMMON_ACCESS,
            AccessPermissions::BEAULAB_HOSPITAL_STATUS_REQUEST_PROCESS,
        ]);

        $this->loginStaff($approver);

        $this->patchJson("/api/v1/staff/hospital-status-change-requests/{$statusChangeRequestId}/decision", [
            'status' => HospitalStatusChangeRequest::STATUS_APPROVED,
        ])
            ->assertOk()
            ->assertJsonPath('data.request.status', HospitalStatusChangeRequest::STATUS_APPROVED);

        self::assertSame(Hospital::STATUS_SUSPENDED, $hospital->refresh()->status);
        self::assertSame(AccountHospital::STATUS_ACTIVE, $accountHospital->refresh()->status);

        $this->startWebSession('hospital', 'http://localhost:3002');
        $this->postJson('/api/v1/hospital/auth/login', [
            'nickname' => 'operation_stop_login_test',
            'password' => 'password1234',
        ])
            ->assertOk()
            ->assertJsonPath('data.actor', 'hospital');
    }

    public function test_direct_operation_stop_cancels_pending_request_without_suspending_hospital_account(): void
    {
        Queue::fake();

        $hospital = Hospital::factory()->active()->create();
        $accountHospital = AccountHospital::factory()->forHospital($hospital)->active()->create();
        $requester = $this->staffWithPermissions([
            AccessPermissions::COMMON_ACCESS,
            AccessPermissions::BEAULAB_HOSPITAL_STATUS_REQUEST_CREATE,
        ]);

        $this->loginStaff($requester);

        $requestId = (int) $this->postJson("/api/v1/staff/hospitals/{$hospital->id}/status-change-requests", [
            'target_status' => Hospital::STATUS_SUSPENDED,
            'reason' => '운영중지 검토 요청',
        ])
            ->assertOk()
            ->json('data.request.id');

        $superAdmin = $this->staffWithPermissions([
            AccessPermissions::COMMON_ACCESS,
            AccessPermissions::BEAULAB_HOSPITAL_STATUS_UPDATE,
        ]);

        $this->loginStaff($superAdmin);

        $this->patchJson("/api/v1/staff/hospitals/{$hospital->id}/status", [
            'status' => Hospital::STATUS_SUSPENDED,
            'reason' => '최고관리자 직접 처리',
        ])
            ->assertOk()
            ->assertJsonPath('data.status', Hospital::STATUS_SUSPENDED);

        self::assertSame(Hospital::STATUS_SUSPENDED, $hospital->refresh()->status);
        self::assertSame(AccountHospital::STATUS_ACTIVE, $accountHospital->refresh()->status);
        self::assertSame(
            HospitalStatusChangeRequest::STATUS_CANCELLED,
            HospitalStatusChangeRequest::query()->findOrFail($requestId)->status,
        );
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
