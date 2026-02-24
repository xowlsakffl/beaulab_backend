<?php

namespace App\Domains\Hospital\Actions\Staff;

use App\Domains\Common\Actions\BusinessRegistration\BusinessRegistrationCreateForStaffAction;
use App\Domains\Common\Actions\Media\MediaAttachAction;
use App\Domains\Hospital\Dto\Staff\HospitalForStaffDetailDto;
use App\Domains\Hospital\Models\Hospital;
use App\Domains\Hospital\Queries\Staff\HospitalCreateForStaffQuery;
use App\Domains\Partner\Actions\HospitalOwnerCreateForStaffAction;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;

final class HospitalCreateForStaffAction
{
    public function __construct(
        private readonly HospitalCreateForStaffQuery $query,
        private readonly MediaAttachAction $mediaAttachAction,
        private readonly BusinessRegistrationCreateForStaffAction $businessRegistrationCreateAction,
    ) {}

    /**
     * @return array{hospital: array}
     */
    public function execute(array $filters): array
    {
        Gate::authorize('create', Hospital::class);

        Log::info('병원 생성', [
            'filters' => array_diff_key($filters, array_flip(['owner_password'])),
        ]);

        $hospital = DB::transaction(function () use ($filters) {
            $hospital = $this->query->create([
                ...$filters,
                'email' => mb_strtolower((string) ($filters['email'] ?? '')) ?: null,
            ]);

            $this->mediaAttachAction->attachLogo($hospital, $filters['logo'], 'hospital');
            $this->mediaAttachAction->attachGallery($hospital, $filters['gallery'], 'hospital');

            $this->businessRegistrationCreateAction->execute($hospital, $filters);

            return $hospital->fresh();
        });

        return [
            'hospital' => HospitalForStaffDetailDto::fromModel(
                $hospital->load(['businessRegistration.certificateMedia', 'logoMedia', 'galleryMedia']),
                ['business_registration'],
            )->toArray(),
        ];
    }
}
