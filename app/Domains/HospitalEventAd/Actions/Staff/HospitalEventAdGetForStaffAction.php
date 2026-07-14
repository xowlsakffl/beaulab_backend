<?php

namespace App\Domains\HospitalEventAd\Actions\Staff;

use App\Domains\HospitalEventAd\Dto\Staff\HospitalEventAdForStaffDetailDto;
use App\Domains\HospitalEventAd\Models\HospitalEventAd;
use Illuminate\Support\Facades\Gate;

final class HospitalEventAdGetForStaffAction
{
    public function execute(HospitalEventAd $ad): array
    {
        Gate::authorize('view', $ad);

        return [
            'hospital_event_ad' => HospitalEventAdForStaffDetailDto::fromModel($ad->load($this->detailRelations()))->toArray(),
        ];
    }

    /**
     * @return array<int, string>
     */
    private function detailRelations(): array
    {
        return [
            'hospital',
            'hospitalEvent',
            'categories',
            'managerStaff',
            'adImage',
        ];
    }
}
