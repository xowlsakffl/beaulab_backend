<?php

namespace App\Domains\HospitalEvent\Actions\Staff;

use App\Domains\Common\Cache\Support\StaffSummaryCache;
use App\Domains\HospitalEvent\Models\HospitalEvent;
use Illuminate\Support\Facades\Gate;

final class HospitalEventDeleteForStaffAction
{
    public function execute(HospitalEvent $event): array
    {
        Gate::authorize('delete', $event);

        $event->delete();
        StaffSummaryCache::forget(StaffSummaryCache::DOMAIN_HOSPITAL_EVENT);

        return [
            'deleted' => true,
            'id' => (int) $event->id,
        ];
    }
}
