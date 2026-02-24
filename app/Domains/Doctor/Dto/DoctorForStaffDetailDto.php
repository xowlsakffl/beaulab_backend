<?php

namespace App\Domains\Doctor\Dto\Staff;

use App\Domains\Common\Models\Media\Media;
use App\Domains\Doctor\Models\Doctor;
use Illuminate\Support\Collection;

final readonly class DoctorForStaffDetailDto
{
    public function __construct(
        public array $doctor,
    ) {}

    public static function fromModel(Doctor $doctor): self
    {
        return new self([
            'id' => $doctor->id,
            'hospital_id' => $doctor->hospital_id,
            'sort_order' => (int) $doctor->sort_order,
            'name' => $doctor->name,
            'gender' => $doctor->gender,
            'position' => $doctor->position,
            'career_started_at' => $doctor->career_started_at?->toDateString(),
            'license_number' => $doctor->license_number,
            'is_specialist' => (bool) $doctor->is_specialist,
            'educations' => $doctor->educations ?? [],
            'careers' => $doctor->careers ?? [],
            'etc_contents' => $doctor->etc_contents ?? [],
            'status' => $doctor->status,
            'allow_status' => $doctor->allow_status,
            'profile_image' => self::formatMedia($doctor->profileImage),
            'license_image' => self::formatMedia($doctor->licenseImage),
            'specialist_certificate_image' => self::formatMediaList($doctor->specialistCertificateImages),
            'graduation_certificate' => self::formatMediaList($doctor->graduationCertificates),
            'etc_certificate' => self::formatMediaList($doctor->etcCertificates),
            'created_at' => $doctor->created_at?->toISOString(),
            'updated_at' => $doctor->updated_at?->toISOString(),
        ]);
    }

    public function toArray(): array
    {
        return $this->doctor;
    }

    private static function formatMedia(?Media $media): ?array
    {
        if (! $media) {
            return null;
        }

        return [
            'id' => $media->id,
            'collection' => $media->collection,
            'disk' => $media->disk,
            'path' => $media->path,
            'mime_type' => $media->mime_type,
            'size' => $media->size,
            'width' => $media->width,
            'height' => $media->height,
            'sort_order' => $media->sort_order,
            'created_at' => $media->created_at?->toISOString(),
            'updated_at' => $media->updated_at?->toISOString(),
        ];
    }

    /** @return array<int, array<string, mixed>> */
    private static function formatMediaList(Collection $mediaList): array
    {
        return $mediaList->map(fn (Media $media): array => self::formatMedia($media) ?? [])->all();
    }
}
