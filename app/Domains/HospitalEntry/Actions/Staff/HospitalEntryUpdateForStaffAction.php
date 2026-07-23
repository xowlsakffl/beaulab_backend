<?php

declare(strict_types=1);

namespace App\Domains\HospitalEntry\Actions\Staff;

use App\Domains\Common\Cache\Support\StaffSummaryCache;
use App\Domains\Common\Media\Actions\MediaAttachDeleteAction;
use App\Domains\HospitalEntry\Dto\Staff\HospitalEntryForStaffDetailDto;
use App\Domains\HospitalEntry\Models\HospitalEntry;
use App\Domains\HospitalEntry\Queries\Staff\HospitalEntryUpdateForStaffQuery;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

final class HospitalEntryUpdateForStaffAction
{
    public function __construct(
        private readonly HospitalEntryUpdateForStaffQuery $query,
        private readonly MediaAttachDeleteAction $mediaAttachAction,
        private readonly HospitalEntryUpdateHistoryRecordAction $historyRecordAction,
    ) {}

    public function execute(HospitalEntry $entry, array $payload): array
    {
        Gate::authorize('update', $entry);

        $beforeHistory = $this->historyRecordAction->capture($entry);

        $updated = DB::transaction(function () use ($entry, $payload, $beforeHistory): HospitalEntry {
            $updated = $this->query->update($entry, $payload);
            $this->replaceMedia($updated, $payload);
            $this->historyRecordAction->recordUpdated($updated, $beforeHistory);

            return $updated->fresh();
        });

        if (array_key_exists('allow_status', $payload)) {
            StaffSummaryCache::forget(StaffSummaryCache::DOMAIN_HOSPITAL_ENTRY);
        }

        return [
            'hospital_entry' => HospitalEntryForStaffDetailDto::fromModel($updated->load([
                'businessRegistrationFile',
                'licenseFile',
            ]))->toArray(),
        ];
    }

    private function replaceMedia(HospitalEntry $entry, array $payload): void
    {
        if (($payload['business_registration_file'] ?? null) instanceof UploadedFile) {
            $this->mediaAttachAction->deleteCollectionMedia($entry, 'hospital_entry_business_registration_file');
            $this->mediaAttachAction->attachOne(
                $entry,
                $payload['business_registration_file'],
                'hospital_entry_business_registration_file',
                'hospital-entry',
                'business-registration-file',
                true,
            );
        } elseif (array_key_exists('existing_business_registration_file_id', $payload) && empty($payload['existing_business_registration_file_id'])) {
            $this->mediaAttachAction->deleteCollectionMedia($entry, 'hospital_entry_business_registration_file');
        }

        if (($payload['license_file'] ?? null) instanceof UploadedFile) {
            $this->mediaAttachAction->deleteCollectionMedia($entry, 'hospital_entry_license_file');
            $this->mediaAttachAction->attachOne(
                $entry,
                $payload['license_file'],
                'hospital_entry_license_file',
                'hospital-entry',
                'license-file',
                true,
            );
        } elseif (array_key_exists('existing_license_file_id', $payload) && empty($payload['existing_license_file_id'])) {
            $this->mediaAttachAction->deleteCollectionMedia($entry, 'hospital_entry_license_file');
        }
    }
}
