<?php

namespace Database\Seeders;

use App\Common\Support\DevelopmentDataGuard;
use App\Domains\AccountStaff\Models\AccountStaff;
use App\Domains\EventExposure\Models\EventExposure;
use App\Domains\EventExposure\Queries\EventExposureQuery;
use App\Domains\ReviewExposure\Models\ReviewExposure;
use App\Domains\ReviewExposure\Queries\ReviewExposureQuery;
use App\Domains\VideoExposure\Models\VideoExposure;
use App\Domains\VideoExposure\Queries\VideoExposureQuery;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

final class ContentExposureDevelopmentSeeder extends Seeder
{
    public function run(): void
    {
        DevelopmentDataGuard::assertEnabled();
        DB::transaction(fn () => $this->seedPlacements());
    }

    private function seedPlacements(): void
    {
        $staff = AccountStaff::where('nickname', 'test_super_admin')->firstOrFail();
        foreach ([
            [EventExposure::class, EventExposureQuery::class, 'events', 'event', 'hospital_event_id'],
            [ReviewExposure::class, ReviewExposureQuery::class, 'reviews', 'review', 'hospital_review_id'],
            [VideoExposure::class, VideoExposureQuery::class, 'videos', 'video', 'hospital_video_id'],
        ] as [$model, $queryClass, $method, $prefix, $field]) {
            $query = app($queryClass);
            foreach ($model::PLACEMENTS as $placement) {
                $scope = $query->scope($placement);
                $builder = $query->{$method}($scope)->where('created_at', '<=', now()->subDays(25));
                if ($prefix === 'event') {
                    $builder->where('is_event_period_unlimited', true);
                }
                $target = $builder->orderBy('id')->first();
                if (! $target) {
                    throw new \LogicException("{$placement} {$prefix} 노출에는 25일 이전에 등록된 공개 콘텐츠가 필요합니다.");
                }
                // 시나리오의 기간은 서로 겹치지 않으며 기존 운영 설정은 덮어쓰지 않는다.
                if ($model::withTrashed()->where('position_key', $scope['key'])->exists()) {
                    continue;
                }
                DB::table($prefix.'_exposure_positions')->insertOrIgnore(['key' => $scope['key'], 'revision' => 0]);
                foreach ([[-20, -15, 'ACTIVE'], [-2, 7, 'ACTIVE'], [10, 15, 'ACTIVE'], [20, 25, 'INACTIVE']] as $index => [$start, $end, $status]) {
                    $entry = $model::create([
                        'position_key' => $scope['key'], $field => $target->id,
                        'created_by_staff_id' => $staff->id, 'start_at' => now()->addDays($start),
                        'end_at' => now()->addDays($end), 'status' => $status,
                        'created_at' => now()->subDays(25), 'updated_at' => now()->subDays(25),
                    ]);
                    DB::table($prefix.'_exposure_metrics')->insert([
                        'position_key' => $scope['key'], 'source_key' => $prefix.':'.$entry->id,
                        'click_count' => $index < 2 ? 37 + $index * 19 : 0,
                    ]);
                }
            }
        }
    }
}
