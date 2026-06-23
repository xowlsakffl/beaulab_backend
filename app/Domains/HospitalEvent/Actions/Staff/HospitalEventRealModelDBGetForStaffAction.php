<?php

declare(strict_types=1);

namespace App\Domains\HospitalEvent\Actions\Staff;

use App\Domains\HospitalEvent\Dto\Staff\HospitalEventRealModelDBForStaffDetailDto;
use App\Domains\HospitalEvent\Models\HospitalEventRealModelDB;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Gate;

final class HospitalEventRealModelDBGetForStaffAction
{
    public function execute(HospitalEventRealModelDB $application): array
    {
        $application->loadMissing([
            'accountUser:id,name,nickname,email,phone,status',
            'hospital:id,name',
            'event:id,hospital_id,name,normal_price,event_price,status,allow_status',
            'event.thumbnailImage',
            'images',
        ]);

        if ($application->event === null) {
            throw (new ModelNotFoundException)->setModel(HospitalEventRealModelDB::class, [$application->id]);
        }

        Gate::authorize('view', $application);

        return HospitalEventRealModelDBForStaffDetailDto::fromModel($application)->toArray();
    }
}
