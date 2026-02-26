<?php

namespace App\Domains\Beauty\Actions\Staff;

use App\Domains\Common\Actions\BusinessRegistration\BusinessRegistrationCreateForStaffAction;
use App\Domains\Common\Actions\Media\MediaAttachAction;
use App\Domains\Beauty\Dto\Staff\BeautyForStaffDetailDto;
use App\Domains\Beauty\Models\Beauty;
use App\Domains\Beauty\Queries\Staff\BeautyCreateForStaffQuery;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;

final class BeautyCreateForStaffAction
{
    public function __construct(
        private readonly BeautyCreateForStaffQuery $query,
        private readonly MediaAttachAction $mediaAttachAction,
        private readonly BusinessRegistrationCreateForStaffAction $businessRegistrationCreateAction,
    ) {}

    /**
     * @return array{hospital: array}
     */
    public function execute(array $filters): array
    {
        Gate::authorize('create', Beauty::class);

        Log::info('뷰티업체 생성', [
            'filters' => array_diff_key($filters, array_flip(['owner_password'])),
        ]);

        $beauty = DB::transaction(function () use ($filters) {
            $beauty = $this->query->create([
                ...$filters,
                'email' => mb_strtolower((string) ($filters['email'] ?? '')) ?: null,
            ]);

            $this->mediaAttachAction->attachLogo($beauty, $filters['logo'], 'beauty');
            $this->mediaAttachAction->attachGallery($beauty, $filters['gallery'], 'beauty');

            $this->businessRegistrationCreateAction->execute($beauty, $filters);

            return $beauty->fresh();
        });

        return [
            'beauty' => BeautyForStaffDetailDto::fromModel(
                $beauty->load(['businessRegistration.certificateMedia', 'logoMedia', 'galleryMedia']),
                ['business_registration'],
            )->toArray(),
        ];
    }
}
