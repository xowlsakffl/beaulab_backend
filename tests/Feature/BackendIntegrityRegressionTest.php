<?php

namespace Tests\Feature;

use App\Common\Exceptions\CustomException;
use App\Domains\AccountStaff\Models\AccountStaff;
use App\Domains\Common\Category\Actions\Staff\CategoryCreateForStaffAction;
use App\Domains\Common\Category\Actions\Staff\CategoryUpdateForStaffAction;
use App\Domains\Common\Category\Models\Category;
use App\Domains\Common\ContentReport\Actions\Staff\ContentReportProcessForStaffAction;
use App\Domains\Common\ContentReport\Actions\Staff\ContentReportStateStatusUpdateForStaffAction;
use App\Domains\Common\ContentReport\Models\ContentReportState;
use App\Domains\Hospital\Models\Hospital;
use App\Domains\HospitalDoctor\Models\HospitalDoctor;
use App\Domains\HospitalVideo\Actions\Staff\HospitalVideoUpdateForStaffAction;
use App\Domains\HospitalVideo\Models\HospitalVideo;
use App\Domains\HospitalVideo\Queries\Staff\HospitalVideoSummaryForStaffQuery;
use App\Modules\Staff\Http\Requests\Category\CategoryUpdateForStaffRequest;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\TestCase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use PHPUnit\Framework\Attributes\DataProvider;

final class BackendIntegrityRegressionTest extends TestCase
{
    use RefreshDatabase;

    public function createApplication(): Application
    {
        $app = parent::createApplication();
        if ($app->configurationIsCached() || config('database.connections.mysql.database') !== 'beaulab_testing') {
            throw new \LogicException('격리된 beaulab_testing DB에서만 실행할 수 있습니다.');
        }

        return $app;
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAs(AccountStaff::factory()->create(), 'staff');
        Gate::before(fn () => true);
    }

    public function test_partial_category_request_preserves_omitted_fields(): void
    {
        $category = $this->category('기존 이름', ['code' => 'original', 'group_code' => 'SURGERY']);
        $request = CategoryUpdateForStaffRequest::create('/', 'PATCH', ['status' => 'INACTIVE']);
        $request->setContainer($this->app)->setRedirector($this->app['redirect']);
        $request->validateResolved();
        self::assertSame(['status' => 'INACTIVE'], $request->validated());
        app(CategoryUpdateForStaffAction::class)->execute($category, $request->validated());
        $category->refresh();
        self::assertSame('기존 이름', $category->name);
        self::assertSame('original', $category->code);
        self::assertSame('SURGERY', $category->group_code);
        self::assertSame('INACTIVE', $category->status);
    }

    public function test_category_paths_follow_parent_ids_not_wildcards_or_regex_replacements(): void
    {
        $root = $this->category('분류%_', ['group_code' => 'SURGERY']);
        $child = $this->category('하위', ['parent_id' => $root->id, 'depth' => 2, 'full_path' => '분류%_ > 하위', 'group_code' => 'SURGERY']);
        $leaf = $this->category('마지막', ['parent_id' => $child->id, 'depth' => 3, 'full_path' => '분류%_ > 하위 > 마지막', 'group_code' => 'SURGERY']);
        $other = $this->category('분류다른');
        $unrelated = $this->category('별개', ['parent_id' => $other->id, 'depth' => 2, 'full_path' => '분류다른 > 별개']);
        app(CategoryUpdateForStaffAction::class)->execute($root, ['name' => '가격$1']);
        self::assertSame('가격$1 > 하위', $child->fresh()->full_path);
        self::assertSame('가격$1 > 하위 > 마지막', $leaf->fresh()->full_path);
        self::assertSame('SURGERY', $leaf->fresh()->group_code);
        self::assertSame('분류다른 > 별개', $unrelated->fresh()->full_path);
        self::assertNull($unrelated->fresh()->group_code);
    }

    public function test_existing_category_code_and_group_cannot_be_cleared(): void
    {
        $category = $this->category('분류', ['code' => 'original', 'group_code' => 'SURGERY']);
        $request = CategoryUpdateForStaffRequest::create('/', 'PATCH', ['code' => null, 'group_code' => null]);
        $request->setContainer($this->app)->setRedirector($this->app['redirect']);
        $request->validateResolved();
        try {
            app(CategoryUpdateForStaffAction::class)->execute($category, $request->validated());
            self::fail('기존 연결 식별자를 변경할 수 없어야 합니다.');
        } catch (CustomException $exception) {
            self::assertStringContainsString('변경할 수 없습니다', $exception->getMessage());
        }
        self::assertSame('original', $category->fresh()->code);
        self::assertSame('SURGERY', $category->fresh()->group_code);
        self::assertSame('분류', $category->fresh()->name);
    }

    public function test_new_child_inherits_updated_parent_path_and_group(): void
    {
        $root = $this->category('기존 분류', ['domain' => Category::DOMAIN_HOSPITAL_MEDICAL, 'group_code' => 'SURGERY']);
        app(CategoryUpdateForStaffAction::class)->execute($root, ['name' => '변경 분류']);
        $result = app(CategoryCreateForStaffAction::class)->execute([
            'domain' => Category::DOMAIN_HOSPITAL_MEDICAL, 'parent_id' => $root->id, 'name' => '하위 분류',
        ]);
        self::assertSame('변경 분류 > 하위 분류', $result['category']['full_path']);
        self::assertSame('SURGERY', $result['category']['group_code']);
        self::assertArrayNotHasKey('parent_scope_id', $result['category']);
    }

    public function test_database_rejects_duplicate_root_names_but_allows_other_domains(): void
    {
        $this->category('동일 이름');
        $this->category('동일 이름', ['domain' => Category::DOMAIN_BEAUTY]);
        $this->expectException(UniqueConstraintViolationException::class);
        $this->category('동일 이름');
    }

    public function test_duplicate_category_action_returns_domain_error(): void
    {
        $this->category('동일 이름');
        $this->expectException(CustomException::class);
        app(CategoryCreateForStaffAction::class)->execute(['domain' => Category::DOMAIN_TALK, 'name' => '동일 이름']);
    }

    public function test_hospital_only_video_change_rejects_existing_foreign_doctor(): void
    {
        $first = Hospital::query()->create(['name' => '첫째의원']);
        $second = Hospital::query()->create(['name' => '둘째의원']);
        $doctor = HospitalDoctor::query()->create(['hospital_id' => $first->id, 'name' => '김의사']);
        $video = HospitalVideo::query()->create(['title' => '안내 영상', 'hospital_id' => $first->id, 'doctor_id' => $doctor->id]);
        try {
            app(HospitalVideoUpdateForStaffAction::class)->execute($video, ['hospital_id' => $second->id]);
            self::fail('다른 병의원 의료진을 유지한 변경이 허용되었습니다.');
        } catch (CustomException) {
            self::assertSame($first->id, $video->fresh()->hospital_id);
        }
        app(HospitalVideoUpdateForStaffAction::class)->execute($video, ['hospital_id' => $second->id, 'doctor_id' => null]);
        self::assertSame($second->id, $video->fresh()->hospital_id);
        self::assertNull($video->fresh()->doctor_id);
        $matchingDoctor = HospitalDoctor::query()->create(['hospital_id' => $second->id, 'name' => '이의사']);
        app(HospitalVideoUpdateForStaffAction::class)->execute($video, ['doctor_id' => $matchingDoctor->id]);
        self::assertSame($matchingDoctor->id, $video->fresh()->doctor_id);
    }

    public function test_forced_stop_is_limited_but_report_reception_remains_normal(): void
    {
        $stopped = HospitalVideo::query()->create(['title' => '중지 영상', 'admin_status' => 'FORCED_STOPPED']);
        $reported = HospitalVideo::query()->create(['title' => '신고 영상']);
        ContentReportState::query()->create(['target_type' => HospitalVideo::class, 'target_id' => $reported->id, 'report_status' => 'REPORTED']);
        $query = app(HospitalVideoSummaryForStaffQuery::class);
        $limited = HospitalVideo::query();
        $query->applySummaryFilter($limited, HospitalVideo::SUMMARY_FILTER_LIMITED);
        self::assertSame([$stopped->id], $limited->pluck('id')->all());
        self::assertSame(['normal_videos' => 1, 'limited_videos' => 1, 'reported_videos' => 1], $query->get());
    }

    public static function reportActions(): array
    {
        return [
            [ContentReportStateStatusUpdateForStaffAction::class, 'NORMAL', 'FORCED_STOPPED', 'NORMAL_VISIBLE'],
            [ContentReportProcessForStaffAction::class, 'NORMAL', 'FORCED_STOPPED', 'NORMAL_VISIBLE'],
            [ContentReportStateStatusUpdateForStaffAction::class, 'FORCED_STOPPED', 'NORMAL', 'ADMIN_HIDDEN'],
            [ContentReportProcessForStaffAction::class, 'FORCED_STOPPED', 'NORMAL', 'ADMIN_HIDDEN'],
        ];
    }

    #[DataProvider('reportActions')]
    public function test_report_processing_reloads_target_after_interleaved_change(string $action, string $initial, string $interleaved, string $next): void
    {
        $video = HospitalVideo::query()->create(['title' => '경합 영상', 'admin_status' => $initial]);
        $state = ContentReportState::query()->create(['target_type' => HospitalVideo::class, 'target_id' => $video->id, 'report_status' => 'REPORTED']);
        $changed = false;
        // 첫 조회 결과가 반환된 직후 다른 요청이 상태를 바꾼 상황을 재현한다.
        DB::listen(function (QueryExecuted $query) use (&$changed, $video, $interleaved): void {
            if (! $changed && str_starts_with($query->sql, 'select') && str_contains($query->sql, 'from `hospital_videos`') && ! str_contains($query->sql, 'for update')) {
                $changed = true;
                DB::table('hospital_videos')->where('id', $video->id)->update(['admin_status' => $interleaved]);
            }
        });
        app($action)->execute(['target_type' => 'hospital_video', 'target_id' => $video->id, 'report_status' => $next]);
        self::assertTrue($changed);
        self::assertSame($initial, $video->fresh()->admin_status);
        self::assertSame($next, $state->fresh()->report_status);
    }

    private function category(string $name, array $attributes = []): Category
    {
        return Category::query()->create(array_replace(['domain' => Category::DOMAIN_TALK, 'name' => $name, 'depth' => 1, 'full_path' => $name], $attributes));
    }
}
