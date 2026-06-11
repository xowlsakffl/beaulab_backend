<?php

namespace App\Domains\HospitalEvent\Actions\Staff;

use App\Domains\HospitalEvent\Models\HospitalEvent;
use Illuminate\Support\Facades\Gate;

final class HospitalEventDeleteForStaffAction
{
    public function execute(HospitalEvent $event): array
    {
        Gate::authorize('delete', $event);

        $event->delete();

        return [
            'deleted' => true,
            'id' => (int) $event->id,
        ];
    }
}
