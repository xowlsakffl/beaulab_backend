<?php

declare(strict_types=1);

namespace Tests\Feature\Domains\RemoteConsultation;

use App\Common\Authorization\AccessPermissions as P;
use App\Domains\AccountHospital\Models\AccountHospital;
use App\Domains\AccountStaff\Models\AccountStaff;
use App\Domains\Common\Category\Models\Category;
use App\Domains\Common\Category\Models\CategoryUsage;
use App\Domains\Common\OperationHistory\Models\OperationHistory;
use App\Domains\Hospital\Models\Hospital;
use App\Domains\RemoteConsultation\Actions\RemoteConsultationAssignOnSubmissionAction;
use App\Domains\RemoteConsultation\Models\RemoteConsultation;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\TestCase;
use Illuminate\Support\Facades\Queue;
use Spatie\Permission\Models\Permission;

final class RemoteConsultationFlowTest extends TestCase
{
    use RefreshDatabase;

    public function createApplication(): Application
    {
        $app = parent::createApplication();
        if ($app->configurationIsCached() || config('database.connections.mysql.database') !== 'beaulab_testing') {
            throw new \LogicException('Use uncached beaulab_testing only.');
        }

        return $app;
    }

    public function test_submission_fills_six_and_approval_preserves_assignments(): void
    {
        Queue::fake();
        $eye = $this->category('눈');
        $nose = $this->category('코');
        $hospitals = collect(range(1, 8))->map(fn () => $this->hospital($eye));
        $excluded = $this->hospital($nose);
        $excluded->update(['status' => Hospital::STATUS_SUSPENDED]);
        $c = $this->consultation([$eye, $nose], $hospitals->take(2)->pluck('id')->all());
        app(RemoteConsultationAssignOnSubmissionAction::class)->execute($c->id);
        $assignedIds = $c->quotes()->get()->modelKeys();
        app(RemoteConsultationAssignOnSubmissionAction::class)->execute($c->id);
        self::assertSame($assignedIds, $c->quotes()->get()->modelKeys());
        $this->login(true);
        $this->getJson("/api/v1/staff/remote-consultations/{$c->id}")->assertOk()->assertJsonPath('data.approval_status', 'PENDING')->assertJsonCount(6, 'data.quotes');
        $url = "/api/v1/staff/remote-consultations/{$c->id}/approve";
        $this->postJson($url)->assertOk()->assertJsonPath('data.approval_status', 'APPROVED')->assertJsonCount(6, 'data.quotes');
        $quotes = $c->quotes()->get();
        self::assertSame($assignedIds, $quotes->modelKeys());
        self::assertCount(2, $quotes->where('selection_type', 'SELECTED'));
        self::assertCount(4, $quotes->where('selection_type', 'RANDOM'));
        self::assertCount(6, $quotes->pluck('hospital_id')->unique());
        foreach ($quotes as $q) {
            self::assertSame([['id' => $eye->id, 'name' => '눈']], $q->matched_categories);
            self::assertSame('WAITING', $q->status);
        }
        $this->postJson($url)->assertOk();
        self::assertSame($quotes->modelKeys(), $c->quotes()->get()->modelKeys());
        self::assertSame(1, OperationHistory::where('target_type', $c->getMorphClass())->where('target_id', $c->id)->count());
    }

    public function test_read_and_approve_permissions_are_separate(): void
    {
        Queue::fake();
        $c = $this->consultation([$this->category('눈')]);
        $this->login(false);
        $this->getJson("/api/v1/staff/remote-consultations/{$c->id}")->assertOk();
        $this->postJson("/api/v1/staff/remote-consultations/{$c->id}/approve")->assertForbidden();
        self::assertSame('PENDING', $c->fresh()->approval_status);
        self::assertSame(0, $c->quotes()->count());
    }

    public function test_missing_photo_does_not_approve_or_assign(): void
    {
        Queue::fake();
        $eye = $this->category('눈');
        $other = $this->hospital($this->category('코'));
        $c = $this->consultation([$eye], [$other->id]);
        $c->media()->delete();
        $this->login(true);
        $this->postJson("/api/v1/staff/remote-consultations/{$c->id}/approve")->assertStatus(422);
        self::assertSame('PENDING', $c->fresh()->approval_status);
        self::assertSame(0, $c->quotes()->count());
        $c->update(['requested_hospital_ids' => []]);
        $c->media()->delete();
        $this->postJson("/api/v1/staff/remote-consultations/{$c->id}/approve")->assertStatus(422);
        self::assertSame('PENDING', $c->fresh()->approval_status);
    }

    public function test_quote_is_scoped_to_application_and_unsubmitted_answers_are_hidden(): void
    {
        Queue::fake();
        $eye = $this->category('눈');
        $h = $this->hospital($eye);
        $c = $this->consultation([$eye]);
        $other = $this->consultation([$eye]);
        $q = $c->quotes()->create(['hospital_id' => $h->id, 'hospital_name' => $h->name, 'selection_type' => 'RANDOM', 'matched_categories' => [['id' => $eye->id, 'name' => $eye->name]], 'answer' => '미발송 답변', 'total_amount' => 1000000]);
        $this->login(false);
        $this->getJson("/api/v1/staff/remote-consultations/{$other->id}/quotes/{$q->id}")->assertNotFound();
        $this->getJson("/api/v1/staff/remote-consultations/{$c->id}/quotes/{$q->id}")->assertOk()->assertJsonPath('data.answer', null)->assertJsonPath('data.total_amount', null);
        $q->update(['status' => 'ANSWERED', 'quoted_categories' => [['id' => $eye->id, 'name' => $eye->name]], 'answered_at' => now()]);
        $this->getJson("/api/v1/staff/remote-consultations/{$c->id}")->assertOk()->assertJsonPath('data.answer_count', 1);
        $this->getJson("/api/v1/staff/remote-consultations/{$c->id}/quotes/{$q->id}")->assertOk()->assertJsonPath('data.total_amount', 1000000);
    }

    public function test_list_filters_and_private_image_urls(): void
    {
        Queue::fake();
        $c = $this->consultation([$this->category('눈')]);
        $c->update(['created_at' => '2026-09-30 23:59:00']);
        $this->login(false);
        $this->getJson('/api/v1/staff/remote-consultations?start_date=2026-09-30&end_date=2026-09-30')->assertOk()->assertJsonCount(1, 'data');
        $this->getJson('/api/v1/staff/remote-consultations?start_date=2026-10-01&end_date=2026-10-01')->assertOk()->assertJsonCount(0, 'data');
        self::assertTrue(app(\App\Domains\Common\Category\Queries\Staff\CategoryDeleteForStaffQuery::class)->hasAssignments($c->categories->first()));
        $r = $this->getJson('/api/v1/staff/remote-consultations?q=테스트회원&gender=FEMALE&birth_year_from=1990&birth_year_to=1990')->assertOk();
        self::assertStringContainsString('signature=', $r->json('data.0.image.url'));
        $this->getJson('/api/v1/staff/remote-consultations?start_date=2026-10-01&end_date=2026-09-01')->assertStatus(422);
        $this->getJson('/api/v1/staff/remote-consultations?gender=MALE')->assertOk()->assertJsonCount(0, 'data');
    }

    public function test_review_and_rejection_preserve_unassigned_state_and_permissions(): void
    {
        Queue::fake();
        $c = $this->consultation([$this->category('눈')]);
        $url = "/api/v1/staff/remote-consultations/{$c->id}/status";
        $this->login(false);
        $this->patchJson($url, ['status' => 'REVIEWING'])->assertForbidden();
        $this->login(true);
        $this->patchJson($url, ['status' => 'REVIEWING'])->assertOk()->assertJsonPath('data.approval_status', 'REVIEWING');
        $this->getJson('/api/v1/staff/remote-consultations?approval_status=REVIEWING')->assertOk()->assertJsonCount(1, 'data');
        $this->patchJson($url, ['status' => 'REJECTED'])->assertOk()->assertJsonPath('data.approval_status', 'REJECTED');
        $this->patchJson($url, ['status' => 'REJECTED'])->assertOk();
        $this->postJson("/api/v1/staff/remote-consultations/{$c->id}/approve")->assertStatus(422);
        $this->patchJson($url, ['status' => 'REVIEWING'])->assertStatus(422);
        $this->patchJson($url, ['status' => 'APPROVED'])->assertStatus(422);
        self::assertSame(0, $c->quotes()->count());
        self::assertSame(2, OperationHistory::where('target_type', $c->getMorphClass())->where('target_id', $c->id)->count());
    }

    public function test_review_can_be_approved_but_approved_cannot_be_rejected(): void
    {
        Queue::fake();
        $category = $this->category('눈');
        $this->hospital($category);
        $c = $this->consultation([$category]);
        app(RemoteConsultationAssignOnSubmissionAction::class)->execute($c->id);
        $this->login(true);
        $url = "/api/v1/staff/remote-consultations/{$c->id}";
        $this->patchJson("{$url}/status", ['status' => 'REVIEWING'])->assertOk();
        $this->postJson("{$url}/approve")->assertOk()->assertJsonPath('data.approval_status', 'APPROVED');
        $this->patchJson("{$url}/status", ['status' => 'REJECTED'])->assertStatus(422);
        self::assertSame(1, $c->quotes()->count());
    }

    public function test_list_sorting_uses_status_order_and_validates_sort_parameters(): void
    {
        $category = $this->category('눈');
        $ids = [];
        foreach (['PENDING', 'REVIEWING', 'APPROVED', 'REJECTED'] as $index => $status) {
            $c = $this->consultation([$category]);
            $c->update(['approval_status' => $status, 'created_at' => now()->subDays(4 - $index)]);
            $ids[] = $c->id;
        }
        $this->login(false);
        foreach (['id', 'created_at', 'approval_status', 'answer_count'] as $sort) {
            $url = "/api/v1/staff/remote-consultations?sort={$sort}";
            self::assertSame($ids, array_column($this->getJson("{$url}&direction=asc")->assertOk()->json('data'), 'id'));
            self::assertSame(array_reverse($ids), array_column($this->getJson("{$url}&direction=desc")->assertOk()->json('data'), 'id'));
        }
        $this->getJson('/api/v1/staff/remote-consultations?sort=unknown')->assertStatus(422);
        $this->getJson('/api/v1/staff/remote-consultations?direction=unknown')->assertStatus(422);
    }

    public function test_staff_can_search_add_remove_and_approve_current_hospital_list(): void
    {
        Queue::fake();
        $category = $this->category('눈');
        $hospital = $this->hospital($category);
        $hospital->businessRegistration()->create([
            'business_number' => '123-45-67890',
            'company_name' => '뷰랩의원',
            'ceo_name' => '김대표',
        ]);
        $other = $this->hospital($this->category('코'));
        $c = $this->consultation([$category]);
        $url = "/api/v1/staff/remote-consultations/{$c->id}";
        $this->login(false);
        $this->getJson("{$url}/hospitals")->assertForbidden();
        $this->postJson("{$url}/quotes", ['hospital_id' => $hospital->id])->assertForbidden();
        $this->login(true);
        $this->getJson("{$url}/hospitals")->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.id', $hospital->id);
        foreach (['123-45-67890', '1234567890', '123 45 67890', '45678'] as $term) {
            $this->getJson("{$url}/hospitals?q=".urlencode($term))->assertOk()
                ->assertJsonCount(1, 'data')->assertJsonPath('data.0.business_number', '123-45-67890');
        }
        $this->getJson("{$url}/hospitals?q=9999999999")->assertOk()->assertJsonCount(0, 'data');
        $this->postJson("{$url}/quotes", ['hospital_id' => $other->id])->assertStatus(422);
        $this->postJson("{$url}/quotes", ['hospital_id' => $hospital->id])->assertOk()->assertJsonCount(1, 'data.quotes');
        $this->postJson("{$url}/quotes", ['hospital_id' => $hospital->id])->assertOk()->assertJsonCount(1, 'data.quotes');
        $this->getJson("{$url}/hospitals")->assertOk()->assertJsonCount(0, 'data');
        $quote = $c->quotes()->firstOrFail();
        $another = $this->consultation([$category]);
        $this->deleteJson("/api/v1/staff/remote-consultations/{$another->id}/quotes/{$quote->id}")->assertNotFound();
        $this->deleteJson("{$url}/quotes/{$quote->id}")->assertOk()->assertJsonCount(0, 'data.quotes');
        $this->postJson("{$url}/quotes", ['hospital_id' => $hospital->id])->assertOk();
        $quote = $c->quotes()->firstOrFail();
        $this->postJson("{$url}/approve")->assertOk()->assertJsonPath('data.quotes.0.id', $quote->id);
        $this->deleteJson("{$url}/quotes/{$quote->id}")->assertStatus(422);
        $this->postJson("{$url}/quotes", ['hospital_id' => $other->id])->assertStatus(422);
    }

    public function test_assignment_capacity_and_completed_quote_protection(): void
    {
        $category = $this->category('눈');
        $hospitals = collect(range(1, 7))->map(fn () => $this->hospital($category));
        $c = $this->consultation([$category]);
        $this->login(true);
        $url = "/api/v1/staff/remote-consultations/{$c->id}";
        foreach ($hospitals->take(6) as $hospital) {
            $this->postJson("{$url}/quotes", ['hospital_id' => $hospital->id])->assertOk();
        }
        $this->postJson("{$url}/quotes", ['hospital_id' => $hospitals->last()->id])->assertStatus(422);
        $quote = $c->quotes()->firstOrFail();
        $quote->update(['status' => 'ANSWERED', 'answered_at' => now()]);
        $this->deleteJson("{$url}/quotes/{$quote->id}")->assertStatus(422);
        self::assertSame(6, $c->quotes()->count());
    }

    public function test_bulk_assignment_is_atomic_and_idempotent(): void
    {
        $category = $this->category('눈');
        $first = $this->hospital($category);
        $second = $this->hospital($category);
        $invalid = $this->hospital($this->category('코'));
        $c = $this->consultation([$category]);
        $this->login(true);
        $url = "/api/v1/staff/remote-consultations/{$c->id}/quotes";
        $this->postJson($url, ['hospital_ids' => [$first->id, $invalid->id]])->assertStatus(422);
        self::assertSame(0, $c->quotes()->count());
        $this->postJson($url, ['hospital_ids' => [$first->id, $second->id]])->assertOk()->assertJsonCount(2, 'data.quotes');
        $this->postJson($url, ['hospital_ids' => [$first->id, $second->id]])->assertOk()->assertJsonCount(2, 'data.quotes');
    }

    private function category(string $name): Category
    {
        $c = Category::factory()->create(['name' => $name]);
        CategoryUsage::create(['category_id' => $c->id, 'usage' => CategoryUsage::USAGE_HOSPITAL_DOCTOR_SUBJECT, 'sort_order' => 1, 'status' => 'ACTIVE']);

        return $c;
    }

    public function test_pending_priority_and_navigation_badge_follow_application_status(): void
    {
        $category = $this->category('눈');
        $pending = $this->consultation([$category]);
        $pending->update(['created_at' => now()->subDays(3)]);
        $reviewing = $this->consultation([$category]);
        $reviewing->update(['approval_status' => 'REVIEWING', 'created_at' => now()->subDays(2)]);
        $approved = $this->consultation([$category]);
        $approved->update(['approval_status' => 'APPROVED']);
        $this->login(true);
        $this->getJson('/api/v1/staff/remote-consultations')->assertOk()
            ->assertJsonPath('data.0.id', $reviewing->id)
            ->assertJsonPath('data.1.id', $pending->id)
            ->assertJsonPath('data.2.id', $approved->id);
        $this->getJson('/api/v1/staff/remote-consultations?sort=id&direction=desc')->assertOk()
            ->assertJsonPath('data.0.id', $approved->id);
        $badges = $this->getJson('/api/v1/staff/navigation-badges')->assertOk()->json('data.badges');
        self::assertSame(['count' => 1, 'has_new' => true], $badges['/customer-db-manage/consultations']);
        $this->patchJson("/api/v1/staff/remote-consultations/{$pending->id}/status", ['status' => 'REVIEWING'])->assertOk();
        $badges = $this->getJson('/api/v1/staff/navigation-badges')->assertOk()->json('data.badges');
        self::assertSame(['count' => 0, 'has_new' => false], $badges['/customer-db-manage/consultations']);
    }

    private function hospital(Category $category): Hospital
    {
        $h = Hospital::factory()->active()->create(['allow_status' => Hospital::ALLOW_APPROVED]);
        $h->categories()->attach($category->id);
        AccountHospital::factory()->forHospital($h)->active()->create();

        return $h;
    }

    public function test_other_customer_db_lists_prioritize_applications_before_pagination(): void
    {
        $hospital = $this->hospital($this->category('눈'));
        $event = \App\Domains\HospitalEvent\Models\HospitalEvent::factory()->forHospital($hospital)->create();
        $user = \App\Domains\AccountUser\Models\AccountUser::factory()->create();
        $base = ['hospital_id' => $hospital->id, 'hospital_event_id' => $event->id, 'account_user_id' => $user->id, 'name' => '테스트 회원', 'phone' => '01000000000', 'phone_normalized' => '01000000000', 'created_at' => now(), 'updated_at' => now()];
        $cases = [
            ['hospital_event_dbs', 'NEW', 'CONFIRMED', \App\Domains\HospitalEvent\Queries\Staff\HospitalEventDBListForStaffQuery::class, ['contact_method' => 'PHONE', 'preferred_time' => 'ANYTIME']],
            ['hospital_event_real_model_dbs', 'RECEIVED', 'APPROVED', \App\Domains\HospitalEvent\Queries\Staff\HospitalEventRealModelDBListForStaffQuery::class, ['gender' => 'FEMALE', 'birth_date' => '1990-01-01', 'height_cm' => 160, 'weight_kg' => 50, 'surgery_period' => 'UNDECIDED', 'support_part' => '눈', 'application_reason' => '상담 신청']],
        ];
        foreach ($cases as [$table, $pending, $processed, $query, $extra]) {
            $first = \Illuminate\Support\Facades\DB::table($table)->insertGetId([...$base, ...$extra, 'status' => $pending]);
            $second = \Illuminate\Support\Facades\DB::table($table)->insertGetId([...$base, ...$extra, 'status' => $processed]);
            self::assertSame($first, app($query)->paginate(['sort' => null, 'per_page' => 1])->items()[0]->id);
            self::assertSame($second, app($query)->paginate(['sort' => 'id', 'direction' => 'desc', 'per_page' => 1])->items()[0]->id);
        }
    }

    private function consultation(array $categories, array $selected = []): RemoteConsultation
    {
        $c = RemoteConsultation::create(['name' => '테스트회원', 'birth_date' => '1990-01-01', 'gender' => 'FEMALE', 'phone' => '01000000000', 'surgery_timing' => 'UNDECIDED', 'special_conditions' => [], 'requested_hospital_ids' => $selected]);
        foreach ($categories as $category) {
            $c->categories()->attach($category->id, ['category_name' => $category->name]);
        }
        $c->media()->create(['collection' => 'member_images', 'disk' => 'private_media', 'path' => 'test.png', 'mime_type' => 'image/png', 'size' => 1, 'metadata' => ['slot' => 'FRONT']]);

        return $c;
    }

    private function login(bool $approve): void
    {
        $staff = AccountStaff::factory()->create(['password' => 'password1234']);
        $permissions = [P::COMMON_ACCESS, P::BEAULAB_REMOTE_CONSULTATION_SHOW];
        if ($approve) {
            $permissions[] = P::BEAULAB_REMOTE_CONSULTATION_APPROVE;
        }
        foreach ($permissions as $permission) {
            $staff->givePermissionTo(Permission::findOrCreate($permission, 'staff'));
        }
        $this->app['auth']->forgetGuards();
        $this->withCredentials()->withHeaders(['X-Beaulab-Client' => 'web', 'Origin' => 'http://localhost:3000']);
        $r = $this->getJson('/api/v1/staff/auth/csrf')->assertOk();
        $this->withCookie('beaulab_staff_session', $r->getCookie('beaulab_staff_session')->getValue());
        $this->withHeader('X-CSRF-TOKEN', $r->json('data.csrf_token'));
        $this->app['auth']->forgetGuards();
        $r = $this->postJson('/api/v1/staff/auth/login', ['nickname' => $staff->nickname, 'password' => 'password1234'])->assertOk();
        $this->withCookie('beaulab_staff_session', $r->getCookie('beaulab_staff_session')->getValue());
        $this->app['auth']->forgetGuards();
    }
}
