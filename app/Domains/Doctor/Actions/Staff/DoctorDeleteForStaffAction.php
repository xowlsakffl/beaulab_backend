<?php

namespace App\Domains\Doctor\Actions\Staff;

use App\Domains\Doctor\Models\Doctor;
use App\Domains\Doctor\Queries\Staff\DoctorDeleteForStaffQuery;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

final class DoctorDeleteForStaffAction
{
    public function __construct(private readonly DoctorDeleteForStaffQuery $query) {}

    public function execute(Doctor $doctor): array
    {
        Gate::authorize('delete', $doctor);

        return DB::transaction(function () use ($doctor) {
            $this->query->softDelete($doctor);
            $doctor->refresh();

            return [
                'deleted_id' => (int) $doctor->id,
                'deleted_at' => optional($doctor->deleted_at)?->toISOString(),
            ];
        });
    }
}
