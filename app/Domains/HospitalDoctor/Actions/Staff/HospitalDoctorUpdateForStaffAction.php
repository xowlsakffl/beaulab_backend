<?php

namespace App\Domains\HospitalDoctor\Actions\Staff;

use App\Domains\Common\Media\Actions\MediaAttachDeleteAction;
use App\Domains\HospitalDoctor\Dto\Staff\HospitalDoctorForStaffDetailDto;
use App\Domains\HospitalDoctor\Models\HospitalDoctor;
use App\Domains\HospitalDoctor\Queries\Staff\HospitalDoctorUpdateForStaffQuery;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

/**
 * HospitalDoctorUpdateForStaffAction 역할 정의.
 * 병원 의사 도메인의 Action 계층으로, 컨트롤러에서 넘어온 검증된 입력을 받아 권한 확인, 도메인 흐름 조합, Query 호출을 담당한다.
 */
final class HospitalDoctorUpdateForStaffAction
{
    public function __construct(
        private readonly HospitalDoctorUpdateForStaffQuery $query,
        private readonly MediaAttachDeleteAction $mediaAttachAction,
        private readonly HospitalDoctorUpdateHistoryRecordAction $historyRecordAction,
    ) {}

    public function execute(HospitalDoctor $doctor, array $payload): array
    {
        Gate::authorize('update', $doctor);
        if (array_key_exists('status', $payload) || array_key_exists('allow_status', $payload)) {
            Gate::authorize('updateStatus', $doctor);
        }

        $beforeHistory = $this->historyRecordAction->capture($doctor);
        $historyReason = $this->historyReason($doctor, $payload);

        $doctor = DB::transaction(function () use ($doctor, $payload, $beforeHistory, $historyReason) {
            $updated = $this->query->update($doctor, $payload);
            $this->replaceMedia($updated, $payload);
            if (array_key_exists('category_ids', $payload) && is_array($payload['category_ids'])) {
                $this->syncCategories($updated, $payload['category_ids']);
            }
            $this->historyRecordAction->recordUpdated($updated, $beforeHistory, $historyReason);

            return $updated->fresh();
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

    private function historyReason(HospitalDoctor $doctor, array $payload): ?string
    {
        if (! array_key_exists('allow_status', $payload)) {
            return null;
        }

        if ((string) $doctor->allow_status === (string) $payload['allow_status']) {
            return null;
        }

        $reason = $payload['reason'] ?? null;

        return is_string($reason) && trim($reason) !== '' ? trim($reason) : null;
    }

    private function replaceMedia(HospitalDoctor $doctor, array $payload): void
    {
        if (($payload['profile_image'] ?? null) instanceof UploadedFile) {
            $this->mediaAttachAction->deleteCollectionMedia($doctor, 'profile_image');
            $this->mediaAttachAction->attachOne($doctor, $payload['profile_image'], 'profile_image', 'doctor', 'profile-image');
        } elseif (array_key_exists('existing_profile_image_id', $payload) && empty($payload['existing_profile_image_id'])) {
            $this->mediaAttachAction->deleteCollectionMedia($doctor, 'profile_image');
        }

        if (($payload['license_image'] ?? null) instanceof UploadedFile) {
            $this->mediaAttachAction->deleteCollectionMedia($doctor, 'license_image');
            $this->mediaAttachAction->attachOne($doctor, $payload['license_image'], 'license_image', 'doctor', 'license-image');
        } elseif (array_key_exists('existing_license_image_id', $payload) && empty($payload['existing_license_image_id'])) {
            $this->mediaAttachAction->deleteCollectionMedia($doctor, 'license_image');
        }

        if (($payload['specialist_certificate_image'] ?? null) instanceof UploadedFile) {
            $this->mediaAttachAction->deleteCollectionMedia($doctor, 'specialist_certificate_image');
            $this->mediaAttachAction->attachOne($doctor, $payload['specialist_certificate_image'], 'specialist_certificate_image', 'doctor', 'specialist-certificate-image');
        } elseif (array_key_exists('existing_specialist_certificate_image_id', $payload) && empty($payload['existing_specialist_certificate_image_id'])) {
            $this->mediaAttachAction->deleteCollectionMedia($doctor, 'specialist_certificate_image');
        }
    }

    /**
     * @param  array<int, int|string>  $categoryIds
     */
    private function syncCategories(HospitalDoctor $doctor, array $categoryIds): void
    {
        $payload = collect($categoryIds)
            ->map(static fn (int|string $categoryId): int => (int) $categoryId)
            ->filter(static fn (int $categoryId): bool => $categoryId > 0)
            ->unique()
            ->values()
            ->mapWithKeys(static fn (int $categoryId, int $index): array => [
                $categoryId => ['is_primary' => $index === 0],
            ])
            ->all();

        $doctor->categories()->sync($payload);
    }
}
