<?php

declare(strict_types=1);

namespace App\Domains\HospitalEntry\Actions\Staff;

use App\Domains\Common\OperationHistory\Actions\OperationHistoryCreateAction;
use App\Domains\Common\OperationHistory\Models\OperationHistory;
use App\Domains\Common\OperationHistory\Support\OperationHistoryChangeSetBuilder;
use App\Domains\HospitalEntry\Models\HospitalEntry;
use Illuminate\Database\Eloquent\Model;

final class HospitalEntryUpdateHistoryRecordAction
{
    public function __construct(
        private readonly OperationHistoryCreateAction $historyCreateAction,
    ) {}

    /**
     * @return array<string, array{label:string,value:mixed,display:?string}>
     */
    public function capture(HospitalEntry $entry): array
    {
        $entry->loadMissing([
            'businessRegistrationFile',
            'licenseFile',
        ]);

        return $this->snapshot($entry);
    }

    /**
     * @param  array<string, array{label:string,value:mixed,display:?string}>  $before
     */
    public function recordUpdated(HospitalEntry $entry, array $before): void
    {
        $entry->load([
            'businessRegistrationFile',
            'licenseFile',
        ]);

        $changes = OperationHistoryChangeSetBuilder::fromSnapshots($before, $this->snapshot($entry));
        if ($changes === []) {
            return;
        }

        $actor = auth()->user();

        $this->historyCreateAction->execute(
            target: $entry,
            action: OperationHistory::ACTION_UPDATED,
            actor: $actor instanceof Model ? $actor : null,
            reason: null,
            metadata: [
                'source' => 'staff.hospital_entry.update',
            ],
            changes: $changes,
        );
    }

    public function recordAllowStatusUpdated(
        HospitalEntry $entry,
        string $beforeStatus,
        string $afterStatus,
        ?string $reason = null,
        bool $bulk = false,
    ): void {
        $changes = OperationHistoryChangeSetBuilder::single(
            key: 'allow_status',
            label: '검수상태',
            before: $beforeStatus,
            after: $afterStatus,
            beforeDisplay: HospitalEntry::allowStatusLabel($beforeStatus),
            afterDisplay: HospitalEntry::allowStatusLabel($afterStatus),
        );

        if ($changes === []) {
            return;
        }

        $actor = auth()->user();

        $this->historyCreateAction->execute(
            target: $entry,
            action: OperationHistory::ACTION_STATE_UPDATED,
            actor: $actor instanceof Model ? $actor : null,
            reason: $reason,
            metadata: [
                'source' => 'staff.hospital_entry.allow_status',
                'bulk' => $bulk,
            ],
            changes: $changes,
        );
    }

    /**
     * @return array<string, array{label:string,value:mixed,display:?string}>
     */
    private function snapshot(HospitalEntry $entry): array
    {
        return [
            'hospital_name' => $this->item('병의원명', $entry->hospital_name, $entry->hospital_name),
            'hospital_phone' => $this->item('전화번호', $entry->hospital_phone, $entry->hospital_phone),
            'address' => $this->item('주소', [
                'address' => $entry->address,
                'address_detail' => $entry->address_detail,
            ], $this->lineList([$entry->address, $entry->address_detail])),
            'business_number' => $this->item('사업자등록번호', $entry->business_number, $entry->business_number),
            'business_registration_file' => $this->item(
                '사업자등록증',
                $entry->businessRegistrationFile?->path,
                $this->mediaLabel($entry->businessRegistrationFile?->path),
            ),
            'ceo_name' => $this->item('대표자', $entry->ceo_name, $entry->ceo_name),
            'license_number' => $this->item('의사면허번호', $entry->license_number, $entry->license_number),
            'license_file' => $this->item(
                '의사면허증',
                $entry->licenseFile?->path,
                $this->mediaLabel($entry->licenseFile?->path),
            ),
            'applicant_name' => $this->item('신청자 이름', $entry->applicant_name, $entry->applicant_name),
            'applicant_position' => $this->item('신청자 직책', $entry->applicant_position, $entry->applicant_position),
            'applicant_phone' => $this->item('신청자 전화번호', $entry->applicant_phone, $entry->applicant_phone),
            'applicant_email' => $this->item('신청자 이메일주소', $entry->applicant_email, $entry->applicant_email),
        ];
    }

    /**
     * @return array{label:string,value:mixed,display:?string}
     */
    private function item(string $label, mixed $value, ?string $display): array
    {
        return compact('label', 'value', 'display');
    }

    private function mediaLabel(?string $path): ?string
    {
        if ($path === null || trim($path) === '') {
            return null;
        }

        return basename($path);
    }

    private function lineList(array $items): ?string
    {
        $items = collect($items)
            ->map(static fn (mixed $item): string => trim((string) $item))
            ->filter(static fn (string $item): bool => $item !== '')
            ->values()
            ->all();

        return $items === [] ? null : implode("\n", $items);
    }
}
