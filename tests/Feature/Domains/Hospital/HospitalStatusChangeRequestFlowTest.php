<?php

declare(strict_types=1);

namespace Tests\Feature\Domains\Hospital;

use App\Common\Authorization\AccessPermissions;
use App\Domains\AccountHospital\Models\AccountHospital;
use App\Domains\AccountStaff\Models\AccountStaff;
use App\Domains\Common\Category\Models\Category;
use App\Domains\Common\ContentReport\Models\ContentReportState;
use App\Domains\Common\Dashboard\Queries\Staff\DashboardWorkForStaffQuery;
use App\Domains\Common\Notification\Actions\StaffNotificationForStaffAction;
use App\Domains\Common\Notification\Actions\StaffNotificationOutboxAction;
use App\Domains\Common\Notification\Actions\StaffNotificationPublisher;
use App\Domains\Common\Notification\Models\NotificationInbox;
use App\Domains\Common\Notification\Models\NotificationPreference;
use App\Domains\Common\Notification\Support\StaffNotificationTypeRegistry;
use App\Domains\Hospital\Models\Hospital;
use App\Domains\Hospital\Models\HospitalStatusChangeRequest;
use App\Domains\HospitalReview\Models\HospitalReview;
use App\Domains\HospitalReview\Models\HospitalReviewComment;
use App\Domains\Talk\Models\Talk;
use App\Domains\Talk\Models\TalkComment;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\TestCase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Spatie\Permission\Models\Permission;

final class HospitalStatusChangeRequestFlowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Category::create(['domain' => Category::DOMAIN_HOSPITAL_DEPARTMENT, 'depth' => 1,
            'code' => 'PLASTIC_SURGERY', 'name' => '성형외과', 'full_path' => '성형외과',
            'sort_order' => 1, 'status' => Category::STATUS_ACTIVE]);
    }

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

    public function test_notification_recipients_and_independent_menu_read_counts(): void
    {
        Queue::fake();
        $first = $this->notificationStaff();
        $second = $this->notificationStaff();
        $actor = $this->notificationStaff();
        $blocked = $this->notificationStaff();
        $blocked->update(['status' => AccountStaff::STATUS_BLOCKED]);
        $readonly = $this->staffWithPermissions([AccessPermissions::BEAULAB_HOSPITAL_SHOW]);
        $muted = $this->notificationStaff();
        NotificationPreference::create(['owner_type' => 'STAFF', 'owner_id' => $muted->id, 'event_type' => 'staff.hospital.requested', 'in_app' => false, 'push' => false, 'email' => false]);
        $hospital = Hospital::factory()->create(['allow_status' => 'PENDING']);
        $outboxId = $this->publishHospital($hospital, 'recipients', (int) $actor->id);
        $recipients = json_decode(DB::table('staff_notification_outbox')->where('id', $outboxId)->value('recipient_ids'), true);
        self::assertEqualsCanonicalizing([$first->id, $second->id], $recipients);
        app(StaffNotificationOutboxAction::class)->process($outboxId);
        app(StaffNotificationOutboxAction::class)->process($outboxId);
        self::assertSame(2, NotificationInbox::count());
        $action = app(StaffNotificationForStaffAction::class);
        $list = $action->listing($first, []);
        self::assertSame(1, $list['unread_count']);
        self::assertSame(1, $list['menu_badges']['/hospital-manage/hospitals']['count']);
        $action->read($first, $list['items'][0]['id'], 1, null);
        self::assertSame(0, $action->unread($first)['unread_count']);
        self::assertSame(1, $action->unread($second)['unread_count']);
        self::assertSame(0, $action->unread($readonly)['unread_count']);
        self::assertSame(0, $action->unread($muted)['unread_count']);
    }

    public function test_notification_read_snapshot_preserves_later_and_other_target_events(): void
    {
        Queue::fake();
        $staff = $this->notificationStaff();
        $first = Hospital::factory()->create(['allow_status' => 'PENDING']);
        $second = Hospital::factory()->create(['allow_status' => 'PENDING']);
        $this->deliverHospital($first, 'first');
        $action = app(StaffNotificationForStaffAction::class);
        $snapshot = $action->unread($staff, ['target_type' => 'hospital', 'target_id' => $first->id]);
        $this->deliverHospital($first, 'later');
        $this->deliverHospital($second, 'other');
        $result = $action->readAll($staff, $snapshot['read_all_token']);
        self::assertSame(1, $result['updated_count']);
        self::assertSame(2, $result['unread_count']);
        self::assertSame(0, $action->readAll($staff, $snapshot['read_all_token'])['updated_count']);
        $otherStaff = $this->notificationStaff();
        try {
            $action->readAll($otherStaff, $snapshot['read_all_token']);
            self::fail('다른 직원의 토큰을 사용할 수 없어야 합니다.');
        } catch (\Illuminate\Validation\ValidationException) {
            self::assertSame(2, $action->unread($staff)['unread_count']);
        }
    }

    public function test_completed_notifications_remain_unread_history_without_reviving_on_reapplication(): void
    {
        Queue::fake();
        $staff = $this->notificationStaff();
        $hospital = Hospital::factory()->create(['allow_status' => 'PENDING']);
        $this->deliverHospital($hospital, 'original');
        $delayed = $this->publishHospital($hospital, 'delayed');
        DB::transaction(function () use ($hospital): void {
            $hospital->update(['allow_status' => 'REJECTED']);
            app(StaffNotificationPublisher::class)->invalidateTarget('hospital', (int) $hospital->id);
        });
        $action = app(StaffNotificationForStaffAction::class);
        self::assertSame(0, $action->unread($staff)['unread_count']);
        self::assertNull(NotificationInbox::firstOrFail()->read_at);
        $hospital->update(['allow_status' => 'PENDING']);
        app(StaffNotificationOutboxAction::class)->process($delayed);
        self::assertSame(1, NotificationInbox::count());
        self::assertSame(0, $action->unread($staff)['unread_count']);
        $this->deliverHospital($hospital, 'reapplied');
        self::assertSame(1, $action->unread($staff)['unread_count']);
        self::assertSame(2, count($action->listing($staff, [])['items']));
        self::assertCount(1, $action->listing($staff, ['unread_only' => true])['items']);
    }

    public function test_notification_delivery_rechecks_permission_status_and_preference(): void
    {
        Queue::fake();
        $staff = $this->notificationStaff();
        $muted = $this->notificationStaff();
        $hospital = Hospital::factory()->create(['allow_status' => 'PENDING']);
        $id = $this->publishHospital($hospital, 'permission-change');
        $staff->revokePermissionTo(AccessPermissions::BEAULAB_HOSPITAL_STATUS_UPDATE);
        NotificationPreference::create(['owner_type' => 'STAFF', 'owner_id' => $muted->id, 'event_type' => 'staff.hospital.requested', 'in_app' => false, 'push' => false, 'email' => false]);
        app(StaffNotificationOutboxAction::class)->process($id);
        self::assertSame(0, NotificationInbox::count());
        self::assertSame(2, DB::table('staff_notification_receipts')->where('status', 'SKIPPED')->count());
    }

    public function test_notification_report_aggregation_and_menu_scoped_read_preserve_new_reports(): void
    {
        Queue::fake();
        $staff = $this->notificationStaff();
        foreach ([AccessPermissions::BEAULAB_REPORTED_TALK_SHOW, AccessPermissions::BEAULAB_REPORTED_TALK_STATUS_UPDATE] as $permission) {
            $staff->givePermissionTo(Permission::findOrCreate($permission, 'staff'));
        }
        $talk = \App\Domains\Talk\Models\Talk::factory()->create();
        $create = app(\App\Domains\Common\ContentReport\Actions\User\ContentReportCreateForUserAction::class);
        $publish = function () use ($create, $talk): void {
            $create->execute(\App\Domains\AccountUser\Models\AccountUser::factory()->create(), $talk, ['reason' => 'OTHER', 'reason_text' => '테스트 신고']);
            foreach (DB::table('staff_notification_outbox')->whereNull('processed_at')->pluck('id') as $id) {
                app(StaffNotificationOutboxAction::class)->process((int) $id);
            }
        };
        $publish();
        $action = app(StaffNotificationForStaffAction::class);
        $first = $action->listing($staff, ['menu_path' => '/reported-post-manage/talks']);
        self::assertSame('/reported-post-manage/talks/'.$talk->id, $first['items'][0]['target_route']);
        $publish();
        self::assertSame(1, $action->unread($staff)['unread_count']);
        self::assertSame(2, $action->listing($staff, [])['items'][0]['event_count']);
        self::assertSame(0, $action->readAll($staff, $first['read_all_token'])['updated_count']);
        $snapshot = $action->unread($staff, ['menu_path' => '/reported-post-manage/talks']);
        $this->deliverHospital(Hospital::factory()->create(['allow_status' => 'PENDING']), 'other-menu');
        $action->readAll($staff, $snapshot['read_all_token']);
        self::assertSame(1, $action->unread($staff)['unread_count']);
        $publish();
        self::assertSame(2, $action->unread($staff)['unread_count']);
        $snapshot = $action->unread($staff, ['target_type' => 'report:talk', 'target_id' => $talk->id]);
        self::assertSame(1, $action->readAll($staff, $snapshot['read_all_token'])['updated_count']);
    }

    public function test_notification_counts_hide_deleted_targets_and_revoked_permissions(): void
    {
        Queue::fake();
        $staff = $this->notificationStaff();
        $hospital = Hospital::factory()->create(['allow_status' => 'PENDING']);
        $this->deliverHospital($hospital, 'hidden-target');
        $action = app(StaffNotificationForStaffAction::class);
        $staff->revokePermissionTo(AccessPermissions::BEAULAB_HOSPITAL_STATUS_UPDATE);
        self::assertSame(0, $action->unread($staff)['unread_count']);
        $staff->givePermissionTo(AccessPermissions::BEAULAB_HOSPITAL_STATUS_UPDATE);
        self::assertSame(1, $action->unread($staff)['unread_count']);
        $hospital->delete();
        self::assertSame(0, $action->unread($staff)['unread_count']);
        self::assertFalse($action->listing($staff, [])['items'][0]['unread_active']);
        self::assertSame(1, NotificationInbox::count());
    }

    private function notificationStaff(): AccountStaff
    {
        $staff = $this->staffWithPermissions([AccessPermissions::BEAULAB_HOSPITAL_SHOW, AccessPermissions::BEAULAB_HOSPITAL_STATUS_UPDATE]);
        $staff->forceFill(['permissions_initialized_at' => now()])->save();

        return $staff;
    }

    public function test_unread_targets_match_menu_counts_without_writes_and_dashboard_rows_expose_targets(): void
    {
        Queue::fake();
        $staff = $this->batchNotificationStaff();
        $hospital = Hospital::factory()->create(['allow_status' => 'PENDING']);
        $request = HospitalStatusChangeRequest::factory()->create(['hospital_id' => $hospital->id, 'requested_by_staff_id' => $staff->id]);
        $talk = Talk::factory()->create();
        $state = ContentReportState::create(['target_type' => Talk::class, 'target_id' => $talk->id, 'report_count' => 9, 'report_status' => 'REPORTED']);
        $inbox = $this->notificationInbox($staff, 'staff.hospital.requested', (int) $hospital->id);
        $this->notificationInbox($staff, NotificationInbox::EVENT_HOSPITAL_STATUS_CHANGE_REQUESTED, (int) $request->id);
        $this->notificationInbox($staff, 'staff.report_talk', (int) $state->id, ['event_count' => 9]);
        $targets = [
            ['target_type' => 'hospital', 'target_id' => (int) $hospital->id],
            ['target_type' => 'report:talk', 'target_id' => (int) $talk->id],
            ['target_type' => 'hospital_status_change_request', 'target_id' => (int) $request->id],
            ['target_type' => 'hospital', 'target_id' => (int) $hospital->id],
            ['target_type' => 'report:unsupported', 'target_id' => 1],
            ['target_type' => 'hospital', 'target_id' => 999999],
        ];
        $action = app(StaffNotificationForStaffAction::class);
        $registry = app(StaffNotificationTypeRegistry::class);
        $registry->allowedTypes($staff);
        DB::enableQueryLog();
        DB::flushQueryLog();
        $result = $action->unreadTargets($staff, $targets);
        $queries = DB::getQueryLog();
        DB::disableQueryLog();
        self::assertSame([2, 1, 1, 2, 0, 0], array_column($result['targets'], 'unread_count'));
        self::assertSame($targets, array_map(fn ($row) => array_diff_key($row, ['unread_count' => true]), $result['targets']));
        self::assertCount(3, $queries);
        foreach ($queries as $query) {
            self::assertStringStartsWith('select ', strtolower($query['query']));
            self::assertStringNotContainsString('for update', strtolower($query['query']));
        }
        self::assertSame(0, DB::table('staff_notification_states')->count());
        self::assertNull($inbox->fresh()->read_at);
        self::assertSame(3, $registry->unreadQuery($staff)->count());
        self::assertSame(2, $registry->badgeCounts($staff)['/hospital-manage/hospitals']['count']);
        self::assertSame(1, $registry->badgeCounts($staff)['/reported-post-manage/talks']['count']);
        $staff->revokePermissionTo(AccessPermissions::BEAULAB_HOSPITAL_STATUS_REQUEST_PROCESS);
        self::assertSame([1, 1, 0, 1, 0, 0], array_column($action->unreadTargets($staff, $targets)['targets'], 'unread_count'));
        $staff->givePermissionTo(AccessPermissions::BEAULAB_HOSPITAL_STATUS_REQUEST_PROCESS);

        $work = app(DashboardWorkForStaffQuery::class)->execute($staff);
        $review = collect($work['reviews'])->firstWhere('key', 'hospitals')['rows'][0];
        $report = collect($work['reports'])->firstWhere('key', 'talks')['rows'][0];
        self::assertSame($targets[0], $review['notification_target']);
        self::assertSame('/hospital-manage/hospitals/'.$hospital->id, $review['href']);
        self::assertSame($targets[1], $report['notification_target']);
        self::assertSame('report:'.$state->id, $report['key']);
        self::assertSame($targets[2], $work['status_requests']['rows'][0]['notification_target']);
        self::assertSame('/hospital-manage/hospitals/'.$hospital->id, $work['status_requests']['rows'][0]['href']);

        $snapshot = $action->unread($staff, $targets[0]);
        $version = (int) DB::table('staff_notification_states')->where('staff_id', $staff->id)->value('version');
        self::assertSame(2, $action->readAll($staff, $snapshot['read_all_token'])['updated_count']);
        self::assertSame([0, 1, 0, 0, 0, 0], array_column($action->unreadTargets($staff, $targets)['targets'], 'unread_count'));
        self::assertSame(0, $registry->badgeCounts($staff)['/hospital-manage/hospitals']['count']);
        self::assertSame($version + 1, (int) DB::table('staff_notification_states')->where('staff_id', $staff->id)->value('version'));
    }

    public function test_unread_targets_exclude_read_resolved_deleted_completed_and_unauthorized_notifications(): void
    {
        Queue::fake();
        $staff = $this->notificationStaff();
        $other = $this->notificationStaff();
        $targets = [];
        foreach (['active', 'read', 'resolved', 'completed', 'deleted', 'other'] as $kind) {
            $hospital = Hospital::factory()->create(['allow_status' => $kind === 'completed' ? 'APPROVED' : 'PENDING']);
            $this->notificationInbox($kind === 'other' ? $other : $staff, 'staff.hospital.requested', (int) $hospital->id, [
                'read_at' => $kind === 'read' ? now() : null,
                'payload' => $kind === 'resolved' ? ['resolved_at' => now()->toIso8601String()] : [],
            ]);
            if ($kind === 'deleted') {
                $hospital->delete();
            }
            $targets[] = ['target_type' => 'hospital', 'target_id' => (int) $hospital->id];
        }
        $action = app(StaffNotificationForStaffAction::class);
        self::assertSame([1, 0, 0, 0, 0, 0], array_column($action->unreadTargets($staff, $targets)['targets'], 'unread_count'));
        $staff->revokePermissionTo(AccessPermissions::BEAULAB_HOSPITAL_STATUS_UPDATE);
        self::assertSame([0, 0, 0, 0, 0, 0], array_column($action->unreadTargets($staff, $targets)['targets'], 'unread_count'));
        $staff->givePermissionTo(AccessPermissions::BEAULAB_HOSPITAL_STATUS_UPDATE);
        $staff->update(['status' => AccountStaff::STATUS_BLOCKED]);
        self::assertSame([0, 0, 0, 0, 0, 0], array_column($action->unreadTargets($staff, $targets)['targets'], 'unread_count'));
    }

    public function test_unread_targets_batch_query_count_does_not_grow_with_target_count(): void
    {
        Queue::fake();
        $staff = $this->batchNotificationStaff();
        $targets = [];
        for ($i = 0; $i < 50; $i++) {
            $hospital = Hospital::factory()->create(['allow_status' => 'PENDING']);
            $request = HospitalStatusChangeRequest::factory()->create(['hospital_id' => $hospital->id, 'requested_by_staff_id' => $staff->id]);
            $talk = Talk::factory()->create();
            $state = ContentReportState::create(['target_type' => Talk::class, 'target_id' => $talk->id, 'report_count' => 1, 'report_status' => 'REPORTED']);
            $this->notificationInbox($staff, 'staff.hospital.requested', (int) $hospital->id);
            $this->notificationInbox($staff, NotificationInbox::EVENT_HOSPITAL_STATUS_CHANGE_REQUESTED, (int) $request->id);
            $this->notificationInbox($staff, 'staff.report_talk', (int) $state->id);
            $targets[] = ['target_type' => 'hospital', 'target_id' => (int) $hospital->id];
            $targets[] = ['target_type' => 'report:talk', 'target_id' => (int) $talk->id];
        }
        $action = app(StaffNotificationForStaffAction::class);
        app(StaffNotificationTypeRegistry::class)->allowedTypes($staff);
        DB::enableQueryLog();
        foreach ([array_slice($targets, 0, 2), $targets] as $batch) {
            DB::flushQueryLog();
            $result = $action->unreadTargets($staff, $batch);
            self::assertCount(3, DB::getQueryLog());
            self::assertCount(count($batch), $result['targets']);
            self::assertSame((int) (count($batch) / 2 * 3), array_sum(array_column($result['targets'], 'unread_count')));
        }
        DB::disableQueryLog();
    }

    public function test_unread_targets_map_report_aliases_to_states_and_keep_domain_and_parent_filters(): void
    {
        Queue::fake();
        $staff = $this->batchNotificationStaff();
        foreach ([AccessPermissions::BEAULAB_REPORTED_HOSPITAL_REVIEW_SHOW, AccessPermissions::BEAULAB_REPORTED_HOSPITAL_REVIEW_STATUS_UPDATE] as $permission) {
            $staff->givePermissionTo(Permission::findOrCreate($permission, 'staff'));
        }
        $talk = Talk::factory()->create();
        $talkComment = TalkComment::factory()->create(['talk_id' => $talk->id]);
        $surgery = HospitalReview::factory()->create(['category_domain' => 'SURGERY']);
        $treatment = HospitalReview::factory()->create(['category_domain' => 'TREATMENT']);
        $reviewComment = HospitalReviewComment::factory()->create(['hospital_review_id' => $surgery->id]);
        $sources = [
            ['talk', $talk, 'staff.report_talk'],
            ['talk_comment', $talkComment, 'staff.report_talk_comment'],
            ['hospital_review', $surgery, 'staff.report_hospital_review_surgery'],
            ['hospital_review', $treatment, 'staff.report_hospital_review_treatment'],
            ['hospital_review_comment', $reviewComment, 'staff.report_hospital_review_comment_surgery'],
        ];
        $targets = [];
        $states = [];
        foreach ($sources as $index => [$alias, $original, $eventType]) {
            $state = ContentReportState::forceCreate([
                'id' => 9000 + $index, 'target_type' => $original::class, 'target_id' => $original->id,
                'report_count' => 5, 'report_status' => 'REPORTED',
            ]);
            self::assertNotSame((int) $original->id, (int) $state->id);
            $states[] = $state;
            $this->notificationInbox($staff, $eventType, (int) $state->id, ['event_count' => 5]);
            $targets[] = ['target_type' => 'report:'.$alias, 'target_id' => (int) $original->id];
        }
        $this->notificationInbox($staff, 'staff.report_hospital_review_treatment', (int) $states[2]->id);
        $action = app(StaffNotificationForStaffAction::class);
        self::assertSame([1, 1, 1, 1, 1], array_column($action->unreadTargets($staff, $targets)['targets'], 'unread_count'));
        $states[3]->update(['report_status' => 'NORMAL_VISIBLE']);
        self::assertSame([1, 1, 1, 0, 1], array_column($action->unreadTargets($staff, $targets)['targets'], 'unread_count'));
        $talk->delete();
        $surgery->delete();
        self::assertSame([0, 0, 0, 0, 0], array_column($action->unreadTargets($staff, $targets)['targets'], 'unread_count'));
        self::assertSame(6, NotificationInbox::whereNull('read_at')->count());
    }

    public function test_unread_targets_http_contract_validation_and_idle_expiry(): void
    {
        Queue::fake();
        $staff = $this->notificationStaff();
        $hospital = Hospital::factory()->create(['allow_status' => 'PENDING']);
        $this->notificationInbox($staff, 'staff.hospital.requested', (int) $hospital->id);
        $this->loginStaff($staff);
        $target = ['target_type' => 'hospital', 'target_id' => (int) $hospital->id];
        $first = $this->postJson('/api/v1/staff/notifications/unread-targets', ['targets' => [$target]])
            ->assertOk()->assertJsonPath('data.targets.0', $target + ['unread_count' => 1]);
        $idle = $first->headers->get('X-Session-Idle-Expires-At');
        self::assertNotNull($idle);
        $this->travel(1)->minutes();
        $this->postJson('/api/v1/staff/notifications/unread-targets', ['targets' => array_fill(0, 100, $target)])
            ->assertOk()->assertJsonCount(100, 'data.targets')->assertHeader('X-Session-Idle-Expires-At', $idle);
        $this->postJson('/api/v1/staff/notifications/unread-targets', ['targets' => []])->assertOk()->assertJsonPath('data.targets', []);
        foreach ([[], ['targets' => null], ['targets' => array_fill(0, 101, $target)], ['targets' => [1 => $target]],
            ['targets' => [['target_id' => 1]]], ['targets' => [['target_type' => 'hospital', 'target_id' => 0]]],
            ['targets' => [['target_type' => 'hospital', 'target_id' => 1.5]]], ['targets' => [$target + ['recipient_id' => $staff->id]]],
            ['targets' => [['target_type' => str_repeat('x', 101), 'target_id' => 1]]]] as $invalid) {
            $this->postJson('/api/v1/staff/notifications/unread-targets', $invalid)->assertStatus(422);
        }
        $this->postJson('/api/v1/staff/notifications/read-all', ['targets' => [$target]])->assertStatus(422);
        self::assertSame(0, DB::table('staff_notification_states')->count());
        self::assertSame(1, NotificationInbox::whereNull('read_at')->count());
        $staff->revokePermissionTo(AccessPermissions::BEAULAB_HOSPITAL_STATUS_UPDATE);
        $this->app['auth']->forgetGuards();
        $this->postJson('/api/v1/staff/notifications/unread-targets', ['targets' => [$target]])->assertOk()->assertJsonPath('data.targets.0.unread_count', 0);
        $staff->update(['status' => AccountStaff::STATUS_BLOCKED]);
        $this->app['auth']->forgetGuards();
        $this->postJson('/api/v1/staff/notifications/unread-targets', ['targets' => [$target]])->assertUnauthorized();
        $this->travelBack();
    }

    private function batchNotificationStaff(): AccountStaff
    {
        $staff = $this->notificationStaff();
        foreach ([AccessPermissions::BEAULAB_HOSPITAL_STATUS_REQUEST_SHOW, AccessPermissions::BEAULAB_HOSPITAL_STATUS_REQUEST_PROCESS,
            AccessPermissions::BEAULAB_REPORTED_TALK_SHOW, AccessPermissions::BEAULAB_REPORTED_TALK_STATUS_UPDATE] as $permission) {
            $staff->givePermissionTo(Permission::findOrCreate($permission, 'staff'));
        }

        return $staff;
    }

    private function notificationInbox(AccountStaff $staff, string $eventType, int $targetId, array $attributes = []): NotificationInbox
    {
        return NotificationInbox::create(array_merge([
            'recipient_type' => NotificationInbox::RECIPIENT_STAFF, 'recipient_id' => $staff->id,
            'event_type' => $eventType, 'target_type' => app(StaffNotificationTypeRegistry::class)->definition($eventType)['target_type'],
            'target_id' => $targetId,
        ], $attributes));
    }

    private function publishHospital(Hospital $hospital, string $key, ?int $actor = null): int
    {
        DB::transaction(fn () => app(StaffNotificationPublisher::class)->record('staff.hospital.requested', $key, 'hospital', (int) $hospital->id, [], null, $actor));

        return (int) DB::table('staff_notification_outbox')->where('event_key', $key)->value('id');
    }

    private function deliverHospital(Hospital $hospital, string $key): void
    {
        app(StaffNotificationOutboxAction::class)->process($this->publishHospital($hospital, $key));
    }

    private function startWebSession(string $actor, string $origin): void
    {
        $this->app['auth']->forgetGuards();
        $this->withCredentials()->withHeaders([
            'X-Beaulab-Client' => 'web',
            'Origin' => $origin,
        ]);
        $response = $this->getJson("/api/v1/{$actor}/auth/csrf")->assertOk();
        if ($cookie = $response->getCookie("beaulab_{$actor}_session")) {
            $this->withCookie("beaulab_{$actor}_session", $cookie->getValue());
        }
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
