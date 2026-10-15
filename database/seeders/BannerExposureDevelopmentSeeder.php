<?php

namespace Database\Seeders;

use App\Common\Support\DevelopmentDataGuard;
use App\Domains\AccountStaff\Models\AccountStaff;
use App\Domains\BannerExposure\Models\BannerExposure;
use App\Domains\BannerExposure\Queries\BannerExposureTargetQuery;
use App\Domains\Common\Media\Actions\MediaAttachDeleteAction;
use App\Domains\Hospital\Models\Hospital;
use App\Domains\HospitalEvent\Models\HospitalEvent;
use App\Domains\HospitalEventAd\Models\HospitalEventAd;
use App\Domains\HospitalEventAd\Queries\Staff\HospitalEventAdSlotAvailabilityForStaffQuery;
use Database\Factories\Support\SeedMediaFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

final class BannerExposureDevelopmentSeeder extends Seeder
{
    public function run(): void
    {
        DevelopmentDataGuard::assertEnabled();
        $connection = config('database.default');
        $disk = config('media.disk', 'public');
        if (! app()->environment(['local', 'testing'])
            || ! in_array(config("database.connections.{$connection}.host"), ['127.0.0.1', 'localhost', '::1'], true)
            || config("filesystems.disks.{$disk}.driver") !== 'local') {
            throw new \LogicException('배너 샘플은 로컬 DB와 로컬 이미지 저장소에서만 생성할 수 있습니다.');
        }
        $hospital = Hospital::where('allow_status', 'APPROVED')->where('status', 'ACTIVE')->firstOrFail();
        $staff = AccountStaff::permission('beaulab.banner_exposure.create')->firstOrFail();
        $category = app(BannerExposureTargetQuery::class)->categories()->firstOrFail();
        $week = now()->startOfWeek(2)->setTime(11, 0);
        if ($week->isFuture()) {
            $week->subWeek();
        }

        DB::transaction(function () use ($hospital, $staff, $category, $week): void {
            app(HospitalEventAdSlotAvailabilityForStaffQuery::class)->lockBookings();
            foreach (BannerExposure::PLACEMENTS as $placement) {
                $label = HospitalEventAd::placementLabel($placement);
                $name = "[샘플] {$label} 이벤트";
                $event = HospitalEvent::withTrashed()->where('name', $name)->first();
                if (! $event) {
                    $event = HospitalEvent::factory()->forHospital($hospital->id)->textType()->create([
                        'name' => $name, 'description' => '배너 연결과 기획전 선택을 확인하는 개발용 이벤트입니다.',
                        'manager_staff_id' => $staff->id, 'allow_status' => 'APPROVED',
                        'hospital_status' => 'PUBLIC', 'admin_status' => 'NORMAL',
                        'is_event_period_unlimited' => false,
                        'event_start_at' => $week->copy()->subMonth(), 'event_end_at' => $week->copy()->addMonths(3),
                    ]);
                    $event->categories()->sync([$category->id => ['is_primary' => true]]);
                    $price = app(\App\Domains\ConsultationPricing\Support\ConsultationPricingResolver::class)->forEvent($event)['price'];
                    $event->forceFill(['base_consultation_price' => $price, 'consultation_price' => $price])->save();
                    $this->image($event, HospitalEvent::COLLECTION_THUMBNAIL_IMAGE);
                }
                if ($event->trashed()) {
                    continue;
                }
                $sampleWeek = $event->created_at->copy()->startOfWeek(2)->setTime(11, 0);
                if ($sampleWeek->greaterThan($event->created_at)) {
                    $sampleWeek->subWeek();
                }

                // 직접 등록이 먼저 채워진 뒤 광고가 들어온 상황을 재현한다.
                $activeCount = $placement === 'MAIN_POPUP' ? 6 : 2;
                $scenarios = [];
                for ($index = 1; $index <= $activeCount; $index++) {
                    $scenarios[] = ["노출중 {$index}", 0, 'ACTIVE', $index % 2 === 0];
                }
                $scenarios = [...$scenarios, ['진행예정', 2, 'ACTIVE', true], ['비공개', 0, 'INACTIVE', false], ['기간종료', -2, 'ACTIVE', false]];
                foreach ($scenarios as [$state, $offset, $status, $exhibition]) {
                    $title = "[노출설정 샘플] {$label} {$state}";
                    if (BannerExposure::withTrashed()->where('placement', $placement)->where('title', $title)->exists()) {
                        continue;
                    }
                    $start = $sampleWeek->copy()->addWeeks($offset);
                    $banner = BannerExposure::create([
                        'placement' => $placement, 'title' => $title,
                        'connection_type' => $exhibition ? 'EXHIBITION' : 'PAGE',
                        'target_type' => $exhibition ? 'EVENT' : 'HOSPITAL',
                        'hospital_event_id' => $exhibition ? $event->id : null,
                        'category_id' => $exhibition ? $category->id : null,
                        'hospital_id' => $exhibition ? null : $hospital->id,
                        'open_mode' => 'IN_APP', 'status' => $status,
                        'start_at' => $start, 'end_at' => $start->copy()->addWeek()->subSecond(),
                        'created_by_staff_id' => $staff->id,
                    ]);
                    $this->image($banner, 'banner');
                    DB::table('banner_exposure_metrics')->insertOrIgnore(['source_key' => "banner:{$banner->id}", 'click_count' => $offset > 0 ? 0 : 120 + $banner->id]);
                }

                foreach ([0, 2, -2] as $offset) {
                    $start = $sampleWeek->copy()->addWeeks($offset);
                    $desired = $placement === 'MAIN_POPUP' && $offset === 0 ? 3 : 1;
                    $owned = HospitalEventAd::withTrashed()->where('placement', $placement)->where('hospital_event_id', $event->id)
                        ->where('start_at', $start)->count();
                    $reserved = HospitalEventAd::where('placement', $placement)->whereIn('allow_status', ['PENDING', 'REVIEWING', 'APPROVED'])
                        ->where('start_at', '<=', $start->copy()->addWeek()->subSecond())->where('end_at', '>=', $start)->count();
                    $count = max(0, min($desired - $owned, 3 - $reserved));
                    for ($index = 0; $index < $count; $index++) {
                        $ad = HospitalEventAd::create([
                            'hospital_id' => $hospital->id, 'hospital_event_id' => $event->id,
                            'manager_staff_id' => $staff->id, 'placement' => $placement,
                            'cost' => 0, 'allow_status' => 'APPROVED',
                            'start_at' => $start, 'end_at' => $start->copy()->addWeek()->subSecond(),
                        ]);
                        $this->image($ad, HospitalEventAd::COLLECTION_AD_IMAGE);
                        DB::table('banner_exposure_metrics')->insertOrIgnore(['source_key' => "ad:{$ad->id}", 'click_count' => $offset > 0 ? 0 : 350 + $ad->id]);
                    }
                }
            }
        });
        $this->command?->info('배너/팝업 샘플을 추가했습니다. 기존 항목과 광고 결제 내역은 변경하지 않았습니다.');
    }

    private function image(Model $owner, string $collection): void
    {
        app(MediaAttachDeleteAction::class)->attachOne(
            $owner, SeedMediaFactory::image('banner-exposure-sample'), $collection,
            'banner-exposure-sample', $collection, true,
        );
    }
}
