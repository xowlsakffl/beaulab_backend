<?php

namespace Database\Seeders;

use App\Common\Support\DevelopmentDataGuard;
use App\Domains\AccountStaff\Models\AccountStaff;
use App\Domains\Common\Media\Actions\MediaAttachDeleteAction;
use App\Domains\HospitalEvent\Models\HospitalEvent;
use App\Domains\HospitalEventAd\Models\HospitalEventAd;
use App\Domains\HospitalEventAd\Queries\Staff\HospitalEventAdSlotAvailabilityForStaffQuery;
use Database\Factories\Support\SeedMediaFactory;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

final class HospitalEventAdDevelopmentSeeder extends Seeder
{
    public function run(): void
    {
        DevelopmentDataGuard::assertEnabled();
        DB::transaction(function (): void {
            app(HospitalEventAdSlotAvailabilityForStaffQuery::class)->lockBookings();
            $staff = AccountStaff::where('nickname', 'test_super_admin')->firstOrFail();
            foreach (HospitalEventAd::placementGroups() as $group => $placements) {
                $division = match ($group) {
                    HospitalEventAd::GROUP_SURGERY => 'SURGERY', HospitalEventAd::GROUP_PETIT => 'TREATMENT', default => 'PROMOTION'
                };
                $event = HospitalEvent::where('allow_status', 'APPROVED')->where('hospital_status', 'PUBLIC')->where('admin_status', 'NORMAL')
                    ->where('is_event_period_unlimited', true)->whereHas('categories', fn ($q) => $q->where('group_code', $division))->orderBy('id')->firstOrFail();
                foreach ($placements as $placement) {
                    if (HospitalEventAd::withTrashed()->where('placement', $placement)->where('hospital_event_id', $event->id)->exists()) {
                        continue;
                    }
                    // 기존 배너 샘플과 겹치지 않는 미래 주차에 상태별 예약을 나눈다.
                    foreach (['PENDING', 'REVIEWING', 'REJECTED', 'APPROVED'] as $index => $status) {
                        $start = now()->next(HospitalEventAd::startDayOfWeek($placement))->setTime(11, 0)->addWeeks(4 + $index);
                        $ad = HospitalEventAd::create([
                            'hospital_id' => $event->hospital_id, 'hospital_event_id' => $event->id,
                            'manager_staff_id' => $staff->id, 'placement' => $placement,
                            'cost' => 0, 'allow_status' => $status, 'start_at' => $start,
                            'end_at' => $start->copy()->addWeek()->subSecond(),
                        ]);
                        if (HospitalEventAd::requiresCategory($placement)) {
                            $root = $event->categories()->wherePivot('is_primary', true)->firstOrFail();
                            while ($root->parent_id) {
                                $root = $root->parent;
                            }
                            $ad->categories()->sync([$root->id => ['is_primary' => true]]);
                        }
                        app(MediaAttachDeleteAction::class)->attachOne($ad, SeedMediaFactory::image('development-ad'), HospitalEventAd::COLLECTION_AD_IMAGE, 'hospital-event-ad', 'ad-image');
                    }
                }
            }
        });
    }
}
