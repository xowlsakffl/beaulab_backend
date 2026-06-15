<?php

namespace App\Domains\HospitalDoctor\Actions\Staff;

use App\Domains\Common\Media\Actions\MediaAttachDeleteAction;
use App\Domains\HospitalDoctor\Dto\Staff\HospitalDoctorForStaffDetailDto;
use App\Domains\HospitalDoctor\Models\HospitalDoctor;
use App\Domains\HospitalDoctor\Queries\Staff\HospitalDoctorCreateForStaffQuery;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

/**
 * HospitalDoctorCreateForStaffAction 역할 정의.
 * 병원 의사 도메인의 Action 계층으로, 컨트롤러에서 넘어온 검증된 입력을 받아 권한 확인, 도메인 흐름 조합, Query 호출을 담당한다.
 */
final class HospitalDoctorCreateForStaffAction
{
    public function __construct(
        private readonly HospitalDoctorCreateForStaffQuery $query,
        private readonly MediaAttachDeleteAction           $mediaAttachAction,
        private readonly HospitalDoctorUpdateHistoryRecordAction $historyRecordAction,
    ) {}

    public function execute(array $payload): array
    {
        Gate::authorize('create', HospitalDoctor::class);

        $doctor = DB::transaction(function () use ($payload) {
            $doctor = $this->query->create($payload);

            $this->mediaAttachAction->attachOne($doctor, $payload['profile_image'] ?? null, 'profile_image', 'doctor', 'profile-image');
            $this->mediaAttachAction->attachOne($doctor, $payload['license_image'] ?? null, 'license_image', 'doctor', 'license-image');
            $this->mediaAttachAction->attachOne($doctor, $payload['specialist_certificate_image'] ?? null, 'specialist_certificate_image', 'doctor', 'specialist-certificate-image');
            $this->syncCategories($doctor, $payload['category_ids'] ?? []);
            $this->historyRecordAction->recordCreated($doctor);

            return $doctor->fresh();
        });

        return [
            'doctor' => HospitalDoctorForStaffDetailDto::fromModel($doctor->load([
                'hospital.businessRegistration',
                'profileImage',
                'licenseImage',
                'specialistCertificateImages',
                'categories',
            ]))->toArray(),
        ];
    }

    /**
     * @param array<int, int|string> $categoryIds
     */
    private function syncCategories(HospitalDoctor $doctor, array $categoryIds): void
    {
        if ($categoryIds === []) {
            return;
        }

        $payload = collect($categoryIds)
            ->map(static fn (int|string $categoryId): int => (int) $categoryId)
            ->filter(static fn (int $categoryId): bool => $categoryId > 0)
            ->unique()
            ->values()
            ->mapWithKeys(static fn (int $categoryId, int $index): array => [
                $categoryId => ['is_primary' => $index === 0],
            ])
            ->all();

        if ($payload === []) {
            return;
        }

        $doctor->categories()->sync($payload);
    }
}
