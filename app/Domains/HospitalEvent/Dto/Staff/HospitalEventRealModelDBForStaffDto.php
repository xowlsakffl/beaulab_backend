<?php

declare(strict_types=1);

namespace App\Domains\HospitalEvent\Dto\Staff;

use App\Domains\AccountUser\Models\AccountUser;
use App\Domains\Common\Media\Models\Media;
use App\Domains\HospitalEvent\Models\HospitalEventRealModelDB;

final readonly class HospitalEventRealModelDBForStaffDto
{
    /**
     * @param  array{first_image?: ?Media, image_count?: int}|null  $imageSummary
     */
    public function __construct(
        private HospitalEventRealModelDB $application,
        private ?array $imageSummary = null,
    ) {}

    /**
     * @param  array{first_image?: ?Media, image_count?: int}|null  $imageSummary
     */
    public static function fromModel(HospitalEventRealModelDB $application, ?array $imageSummary = null): self
    {
        return new self($application, $imageSummary);
    }

    public function toArray(): array
    {
        return [
            'id' => (int) $this->application->id,
            'hospital' => $this->hospital(),
            'event' => $this->event(),
            'account_user' => $this->accountUser(),
            'name' => (string) $this->application->name,
            'gender' => [
                'code' => (string) $this->application->gender,
                'label' => HospitalEventRealModelDB::genderLabel($this->application->gender),
            ],
            'birth_date' => $this->application->birth_date?->toDateString(),
            'phone' => (string) $this->application->phone,
            'phone_normalized' => (string) $this->application->phone_normalized,
            'height_cm' => (int) $this->application->height_cm,
            'weight_kg' => (int) $this->application->weight_kg,
            'surgery_period' => [
                'code' => (string) $this->application->surgery_period,
                'label' => HospitalEventRealModelDB::surgeryPeriodLabel($this->application->surgery_period),
            ],
            'support_part' => (string) $this->application->support_part,
            'instagram_url' => $this->application->instagram_url,
            'blog_url' => $this->application->blog_url,
            'special_notes' => $this->specialNotes(),
            'application_reason' => (string) $this->application->application_reason,
            'inquiry' => $this->application->inquiry,
            'status' => [
                'code' => (string) $this->application->status,
                'label' => HospitalEventRealModelDB::statusLabel($this->application->status),
            ],
            'first_image' => self::media($this->imageSummary['first_image'] ?? null),
            'image_count' => (int) ($this->imageSummary['image_count'] ?? 0),
            'author_ip' => $this->application->author_ip,
            'user_agent' => $this->application->user_agent,
            'created_at' => $this->application->created_at?->toISOString(),
            'updated_at' => $this->application->updated_at?->toISOString(),
        ];
    }

    private function hospital(): ?array
    {
        if (! $this->application->relationLoaded('hospital') || ! $this->application->hospital) {
            return null;
        }

        return [
            'id' => (int) $this->application->hospital->id,
            'name' => (string) $this->application->hospital->name,
        ];
    }

    private function event(): ?array
    {
        if (! $this->application->relationLoaded('event') || ! $this->application->event) {
            return null;
        }

        return [
            'id' => (int) $this->application->event->id,
            'name' => (string) $this->application->event->name,
        ];
    }

    private function accountUser(): ?array
    {
        if (! $this->application->relationLoaded('accountUser') || ! $this->application->accountUser) {
            return null;
        }

        return [
            'id' => (int) $this->application->accountUser->id,
            'name' => $this->application->accountUser->name,
            'nickname' => $this->application->accountUser->nickname,
            'email' => $this->application->accountUser->email,
            'phone' => $this->application->accountUser->phone,
            'status' => [
                'code' => (string) $this->application->accountUser->status,
                'label' => AccountUser::statusLabels()[$this->application->accountUser->status] ?? '-',
            ],
        ];
    }

    /**
     * @return list<array{code: string, label: string}>
     */
    private function specialNotes(): array
    {
        $notes = $this->application->special_notes;

        if (! is_array($notes)) {
            return [];
        }

        return collect($notes)
            ->map(static fn (mixed $note): string => (string) $note)
            ->filter(static fn (string $note): bool => $note !== '')
            ->map(static fn (string $note): array => [
                'code' => $note,
                'label' => HospitalEventRealModelDB::specialNoteLabel($note),
            ])
            ->values()
            ->all();
    }

    private static function media(?Media $media): ?array
    {
        if (! $media instanceof Media) {
            return null;
        }

        return [
            'id' => (int) $media->id,
            'collection' => (string) $media->collection,
            'disk' => (string) $media->disk,
            'path' => (string) $media->path,
            'mime_type' => (string) $media->mime_type,
            'size' => (int) $media->size,
            'width' => $media->width !== null ? (int) $media->width : null,
            'height' => $media->height !== null ? (int) $media->height : null,
            'sort_order' => (int) $media->sort_order,
            'is_primary' => (bool) $media->is_primary,
            'metadata' => $media->metadata,
            'created_at' => $media->created_at?->toISOString(),
            'updated_at' => $media->updated_at?->toISOString(),
        ];
    }
}
