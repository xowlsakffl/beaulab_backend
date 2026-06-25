<?php

namespace App\Domains\HospitalDoctor\Actions\Staff;

use App\Domains\Common\OperationHistory\Actions\OperationHistoryCreateAction;
use App\Domains\Common\OperationHistory\Models\OperationHistory;
use App\Domains\Common\OperationHistory\Support\OperationHistoryChangeSetBuilder;
use App\Domains\HospitalDoctor\Models\HospitalDoctor;
use Illuminate\Database\Eloquent\Model;

final class HospitalDoctorUpdateHistoryRecordAction
{
    public function __construct(
        private readonly OperationHistoryCreateAction $historyCreateAction,
    ) {}

    /**
     * @return array<string, array{label:string,value:mixed,display:?string}>
     */
    public function capture(HospitalDoctor $doctor): array
    {
        $doctor->loadMissing([
            'hospital',
            'profileImage',
            'licenseImage',
            'specialistCertificateImages',
            'categories',
        ]);

        return $this->snapshot($doctor);
    }

    public function recordCreated(HospitalDoctor $doctor): void
    {
        $actor = auth()->user();

        $this->historyCreateAction->execute(
            target: $doctor,
            action: OperationHistory::ACTION_CREATED,
            actor: $actor instanceof Model ? $actor : null,
            reason: null,
            metadata: [
                'source' => 'staff.hospital_doctor.create',
            ],
        );
    }

    /**
     * @param array<string, array{label:string,value:mixed,display:?string}> $before
     */
    public function recordUpdated(HospitalDoctor $doctor, array $before, ?string $reason = null): void
    {
        $doctor->load([
            'hospital',
            'profileImage',
            'licenseImage',
            'specialistCertificateImages',
            'categories',
        ]);

        $changes = $this->changes($before, $this->snapshot($doctor));
        if ($changes === []) {
            return;
        }

        $actor = auth()->user();

        $this->historyCreateAction->execute(
            target: $doctor,
            action: OperationHistory::ACTION_UPDATED,
            actor: $actor instanceof Model ? $actor : null,
            reason: $reason,
            metadata: [
                'source' => 'staff.hospital_doctor.update',
            ],
            changes: $changes,
        );
    }

    /**
     * @return array<string, array{label:string,value:mixed,display:?string}>
     */
    private function snapshot(HospitalDoctor $doctor): array
    {
        return [
            'hospital' => $this->item('병의원', (int) $doctor->hospital_id, $doctor->hospital?->name ?? (string) $doctor->hospital_id),
            'name' => $this->item('의료진명', $doctor->name, $doctor->name),
            'position' => $this->item('직책', $doctor->position, $doctor->position),
            'gender' => $this->item('성별', $doctor->gender, $doctor->gender),
            'career_started_at' => $this->item('경력기간', $doctor->career_started_at?->toDateString(), $doctor->career_started_at?->toDateString()),
            'license_number' => $this->item('의사면허 번호', $doctor->license_number, $doctor->license_number),
            'specialist_field' => $this->item(
                '전문의',
                $doctor->specialist_field,
                HospitalDoctor::specialistFieldLabel($doctor->specialist_field),
            ),
            'categories' => $this->item('진료분야', $this->categoryValue($doctor), $this->categoryDisplay($doctor)),
            'careers' => $this->item('경력사항', $doctor->careers ?? [], $this->arrayDisplay($doctor->careers)),
            'etc_contents' => $this->item('활동사항', $doctor->etc_contents ?? [], $this->arrayDisplay($doctor->etc_contents)),
            'educations' => $this->item('학력사항', $doctor->educations ?? [], $this->arrayDisplay($doctor->educations)),
            'profile_image' => $this->item('프로필 사진', $doctor->profileImage?->path, $this->mediaLabel($doctor->profileImage?->path)),
            'license_image' => $this->item('의사면허증', $doctor->licenseImage?->path, $this->mediaLabel($doctor->licenseImage?->path)),
            'specialist_certificate_image' => $this->item(
                '전문의 증명서',
                $this->certificateValue($doctor),
                $this->certificateDisplay($doctor),
            ),
            'allow_status' => $this->item('검수상태', $doctor->allow_status, HospitalDoctor::allowStatusLabel((string) $doctor->allow_status)),
            'status' => $this->item('의료진상태', $doctor->status, HospitalDoctor::statusLabel((string) $doctor->status)),
        ];
    }

    /**
     * @return array{label:string,value:mixed,display:?string}
     */
    private function item(string $label, mixed $value, ?string $display): array
    {
        return compact('label', 'value', 'display');
    }

    /**
     * @param array<string, array{label:string,value:mixed,display:?string}> $before
     * @param array<string, array{label:string,value:mixed,display:?string}> $after
     * @return array<int, array<string, mixed>>
     */
    private function changes(array $before, array $after): array
    {
        $builder = OperationHistoryChangeSetBuilder::make();

        foreach ($after as $key => $afterItem) {
            $beforeItem = $before[$key] ?? $this->item($afterItem['label'], null, null);
            $builder->compare(
                key: $key,
                label: $afterItem['label'],
                before: $beforeItem['value'],
                after: $afterItem['value'],
                beforeDisplay: $beforeItem['display'],
                afterDisplay: $afterItem['display'],
            );
        }

        return $builder->toArray();
    }

    /**
     * @return array<int, array{id:int,path:string,is_primary:bool}>
     */
    private function categoryValue(HospitalDoctor $doctor): array
    {
        return $doctor->categories
            ->map(static fn ($category): array => [
                'id' => (int) $category->id,
                'path' => (string) ($category->full_path ?: $category->name),
                'is_primary' => (bool) ($category->pivot?->is_primary ?? false),
            ])
            ->sortBy('path')
            ->values()
            ->all();
    }

    private function categoryDisplay(HospitalDoctor $doctor): ?string
    {
        return $this->lineList(collect($this->categoryValue($doctor))
            ->map(static fn (array $category): string => ($category['is_primary'] ? '[대표] ' : '').$category['path'])
            ->all());
    }

    /**
     * @return array<int, string>
     */
    private function normalizedList(mixed $items): array
    {
        if (! is_array($items)) {
            return [];
        }

        return collect($items)
            ->filter(static fn (mixed $item): bool => is_scalar($item))
            ->map(static fn (mixed $item): string => trim((string) $item))
            ->filter(static fn (string $item): bool => $item !== '')
            ->values()
            ->all();
    }

    private function arrayDisplay(mixed $items): ?string
    {
        if (! is_array($items) || $items === []) {
            return null;
        }

        $normalized = $this->normalizedList($items);
        if ($normalized !== []) {
            return $this->lineList($normalized);
        }

        $encoded = json_encode($items, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        return $encoded === false ? null : $encoded;
    }

    private function mediaLabel(?string $path): ?string
    {
        if ($path === null || trim($path) === '') {
            return null;
        }

        return basename($path);
    }

    /**
     * @return array<int, array{id:int,path:string,sort_order:int}>
     */
    private function certificateValue(HospitalDoctor $doctor): array
    {
        return $doctor->specialistCertificateImages
            ->map(static fn ($media): array => [
                'id' => (int) $media->id,
                'path' => (string) $media->path,
                'sort_order' => (int) $media->sort_order,
            ])
            ->sortBy('sort_order')
            ->values()
            ->all();
    }

    private function certificateDisplay(HospitalDoctor $doctor): ?string
    {
        return $this->lineList(collect($this->certificateValue($doctor))
            ->map(fn (array $media): ?string => $this->mediaLabel($media['path']))
            ->all());
    }

    /**
     * @param array<int, mixed> $items
     */
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
