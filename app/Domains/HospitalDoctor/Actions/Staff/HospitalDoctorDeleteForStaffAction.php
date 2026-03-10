<?php

namespace App\Domains\HospitalDoctor\Actions\Staff;

use App\Domains\Common\Actions\Media\MediaAttachDeleteAction;
use App\Domains\HospitalDoctor\Models\HospitalDoctor;
use App\Domains\HospitalDoctor\Queries\Staff\HospitalDoctorDeleteForStaffQuery;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

final class HospitalDoctorDeleteForStaffAction
{
    public function __construct(
        private readonly HospitalDoctorDeleteForStaffQuery $query,
        private readonly MediaAttachDeleteAction           $mediaAttachAction,
    ) {}

    public function execute(HospitalDoctor $doctor): array
    {
        Gate::authorize('delete', $doctor);

        return DB::transaction(function () use ($doctor) {
            $this->mediaAttachAction->deleteCollectionMediaBulk($doctor, [
                'profile_image',
                'license_image',
                'specialist_certificate_image',
                'education_certificate_image',
                'etc_certificate_image',
            ]);
            $doctor->categories()->sync([]);

            $this->query->softDelete($doctor);
            $doctor->refresh();

            return [
                'deleted_id' => (int) $doctor->id,
                'deleted_at' => optional($doctor->deleted_at)?->toISOString(),
            ];
        });
    }
}
