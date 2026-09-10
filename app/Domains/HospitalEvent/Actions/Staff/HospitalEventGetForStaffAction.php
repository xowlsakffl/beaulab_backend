<?php

namespace App\Domains\HospitalEvent\Actions\Staff;

use App\Domains\HospitalEvent\Dto\Staff\HospitalEventForStaffDetailDto;
use App\Domains\HospitalEvent\Models\HospitalEvent;
use Illuminate\Support\Facades\Gate;

final class HospitalEventGetForStaffAction
{
    public function execute(HospitalEvent $event): array
    {
        Gate::authorize('view', $event);

        return [
            'event' => HospitalEventForStaffDetailDto::fromModel($event->load([
                'hospital.businessRegistration',
                'categories',
                'doctors',
                'options',
                'thumbnailImage',
                'eventPageImage',
                'beforeAfterPhotos',
            ]))->toArray(),
        ];
    }
}
