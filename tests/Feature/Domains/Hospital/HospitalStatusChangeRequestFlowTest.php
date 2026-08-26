<?php

declare(strict_types=1);

namespace Tests\Feature\Domains\Hospital;

use App\Common\Authorization\AccessPermissions;
use App\Domains\AccountHospital\Models\AccountHospital;
use App\Domains\AccountStaff\Models\AccountStaff;
use App\Domains\Hospital\Models\Hospital;
use App\Domains\Hospital\Models\HospitalStatusChangeRequest;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\TestCase;
use Illuminate\Support\Facades\Queue;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Permission;

final class HospitalStatusChangeRequestFlowTest extends TestCase
{
    use RefreshDatabase;

    public function createApplication(): Application
    {
        $app = require dirname(__DIR__, 4).'/bootstrap/app.php';
        $app->make(Kernel::class)->bootstrap();

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

        Sanctum::actingAs($requester, ['actor:staff']);

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

        Sanctum::actingAs($approver, ['actor:staff']);

        $this->patchJson("/api/v1/staff/hospital-status-change-requests/{$statusChangeRequestId}/decision", [
            'status' => HospitalStatusChangeRequest::STATUS_APPROVED,
        ])
            ->assertOk()
            ->assertJsonPath('data.request.status', HospitalStatusChangeRequest::STATUS_APPROVED);

        self::assertSame(Hospital::STATUS_SUSPENDED, $hospital->refresh()->status);
        self::assertSame(AccountHospital::STATUS_ACTIVE, $accountHospital->refresh()->status);

        $this->postJson('/api/v1/hospital/auth/login', [
            'nickname' => 'operation_stop_login_test',
            'password' => 'password1234',
            'device_name' => 'feature-test',
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

        Sanctum::actingAs($requester, ['actor:staff']);

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

        Sanctum::actingAs($superAdmin, ['actor:staff']);

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
