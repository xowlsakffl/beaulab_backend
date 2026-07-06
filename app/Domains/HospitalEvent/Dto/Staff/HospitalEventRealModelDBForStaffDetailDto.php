<?php

declare(strict_types=1);

namespace App\Domains\HospitalEvent\Dto\Staff;

use App\Domains\AccountUser\Models\AccountUser;
use App\Domains\Common\Media\Models\Media;
use App\Domains\HospitalEvent\Models\HospitalEvent;
use App\Domains\HospitalEvent\Models\HospitalEventRealModelDB;

final readonly class HospitalEventRealModelDBForStaffDetailDto
{
    private function __construct(private HospitalEventRealModelDB $application) {}

    public static function fromModel(HospitalEventRealModelDB $application): self
    {
        return new self($application);
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
            'images' => $this->images(),
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

        $event = $this->application->event;

        return [
            'id' => (int) $event->id,
            'name' => (string) $event->name,
            'normal_price' => (int) ($event->normal_price ?? 0),
            'event_price' => (int) ($event->event_price ?? 0),
            'hospital_status' => (string) ($event->hospital_status ?? ''),
            'admin_status' => (string) ($event->admin_status ?? ''),
            'allow_status' => (string) ($event->allow_status ?? ''),
            'thumbnail_image' => $event instanceof HospitalEvent && $event->relationLoaded('thumbnailImage')
                ? self::media($event->thumbnailImage)
                : null,
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

    private function images(): array
    {
        if (! $this->application->relationLoaded('images')) {
            return [];
        }

        return $this->application->images
            ->map(static fn (Media $media): array => self::media($media))
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
